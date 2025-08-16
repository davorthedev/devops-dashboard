#!/usr/bin/env python3
import json
import re
import sys
from datetime import datetime, timedelta
from pathlib import Path

LOG_FILE_PATH = "/var/log/auth.log"
FAILED_LOGIN_PATTERN = re.compile(r"Failed password")
SUSPICIOUS_IP_PATTERN = re.compile(r"(\d+\.\d+\.\d+\.\d+)")
SYSLOG_TIMESTAMP_PATTERN = re.compile(r"^[A-Z][a-z]{2}\s+\d{1,2}\s+\d{2}:\d{2}:\d{2}")

def parse_syslog_timestamp(log_line: str) -> datetime | None:
    match = SYSLOG_TIMESTAMP_PATTERN.match(log_line)
    if not match:
        return None

    timestamp_str = match.group(0)
    try:
        parsed_ts = datetime.strptime(timestamp_str, "%b %d %H:%M:%S")

        return parsed_ts.replace(year=datetime.now().year)
    except ValueError:
        return None

def analyze_log(log_file: Path, from_log_datetime: datetime):
    failed_logins = 0
    suspicious_ips = set()

    try:
        with log_file.open("r", encoding="utf-8", errors="replace") as f:
            for log_line in f:
                log_time = parse_syslog_timestamp(log_line)
                if log_time and log_time < from_log_datetime:
                    continue

                if FAILED_LOGIN_PATTERN.search(log_line):
                    failed_logins += 1
                    if ip_match := SUSPICIOUS_IP_PATTERN.search(log_line):
                        suspicious_ips.add(ip_match.group(1))

    except Exception:
        return 0, set()

    return failed_logins, suspicious_ips

def main():
    log_path = Path(sys.argv[1]) if len(sys.argv) > 1 else Path(LOG_FILE_PATH)
    from_log_datetime = datetime.now() - timedelta(days = 1)
    failed_logins, suspicious_ips = analyze_log(log_path, from_log_datetime)
    print(json.dumps({
        "failed_logins": failed_logins,
        "suspicious_ips": sorted(suspicious_ips)
    }))

if __name__ == "__main__":
    main()