#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <sys/statvfs.h>

double get_cpu_usage() {
    FILE *fp;
    unsigned long long int user1, nice1, system1, idle1;
    unsigned long long int user2, nice2, system2, idle2;

    fp = fopen("/proc/stat", "r");
    if (!fp) {
        perror("Error opening proc/stat");

        return -1;
    }
    if (4 != fscanf(fp, "cpu %llu %llu %llu %llu", &user1, &nice1, &system1, &idle1)) {
        fclose(fp);
        fprintf(stderr, "Error reading first CPU statistics\n");

        return -1;
    }
    fclose(fp);

    usleep(100000);

    fp = fopen("/proc/stat", "r");
    if (!fp) {
        perror("Error opening proc/stat");

        return -1;
    }
    if (4 != fscanf(fp, "cpu %llu %llu %llu %llu", &user2, &nice2, &system2, &idle2)) {
        fclose(fp);
        fprintf(stderr, "Error reading second CPU statistics\n");

        return -1;
    }
    fclose(fp);

    unsigned long long int total1 = user1 + nice1 + system1 + idle1;
    unsigned long long int total2 = user2 + nice2 + system2 + idle2;

    unsigned long long int total_diff = total2 - total1;
    unsigned long long int idle_diff = idle2 - idle1;

    if (0 == total_diff) {
        return 0.0;
    }

    return (double)(total_diff - idle_diff) / total_diff * 100.0;
}

double get_mem_usage() {
    FILE *fp;
    fp = fopen("/proc/meminfo", "r");
    if (!fp) {
        perror("Error opening /proc/meminfo");

        return -1;
    }

    char *line = NULL;
    size_t len = 0;
    unsigned long mem_total = 0, mem_free = 0, buffers = 0, cached = 0;

    while (-1 != getline(&line, &len, fp)) {
        if (1 == sscanf(line, "MemTotal: %lu kB", &mem_total)) {
            continue;
        }
        if (1 == sscanf(line, "MemFree: %lu kB", &mem_free)) {
            continue;
        }
        if (1 == sscanf(line, "BufferSize: %lu kB", &buffers)) {
            continue;
        }
        if (1 == sscanf(line, "Cached: %lu kB", &cached)) {
            continue;
        }
    }
    free(line);
    fclose(fp);

    if (0 == mem_total) {
        return -1;
    }

    unsigned long used = mem_total - (mem_free + buffers + cached);

    return (double)used / mem_total * 100.0;
}

double get_disk_usage(const char *path) {
    struct statvfs stat;
    if (0 != statvfs(path, &stat)) {
        perror("statvfs failed");

        return -1;
    }
    unsigned long long total = (unsigned long long)stat.f_blocks * stat.f_frsize;
    unsigned long long free = (unsigned long long)stat.f_bfree * stat.f_frsize;

    if (0 == total) {
        return 0.0;
    }

    unsigned long long used = total - free;

    return (double)used / total * 100.0;
}

int main() {
    double cpu = get_cpu_usage();
    double mem = get_mem_usage();
    double disk = get_disk_usage("/");

    printf(
        "{\"cpu\": %.2f, \"memory\": %.2f, \"disk\": %.2f}\n",
        cpu >= 0 ? cpu : 0,
        mem >= 0 ? mem : 0,
        disk >= 0 ? disk : 0
    );

    return 0;
}