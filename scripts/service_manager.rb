#!/usr/bin/env ruby
# Usage: ruby service_manager.rb start|stop|restart serviceName

action = ARGV[0]&.strip
service = ARGV[1]&.strip

if action.nil? || service.nil?
  puts "Usage: ruby #{__FILE__} start|stop|restart serviceName"
  exit 1
end

valid_actions = %w[start stop restart status]
unless valid_actions.include?(action)
  puts "Invalid action '#{action}'. Use start, stop, restart or status."
  exit 1
end

command = if action == "status"
  "systemctl is-active #{service}"
else
  "sudo systemctl #{action} #{service}"
end

output = `#{command} 2>&1`.strip
exit_code = $?.exitstatus

if exit_code == 0
  if action == "status"
    puts output
  else
    puts "Service '#{service}' #{action} successfully."
  end
else
  puts "Error managing service '#{service}': #{output}"
  exit exit_code
end
