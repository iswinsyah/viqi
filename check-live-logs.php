<?php
echo "--- AGENT CRON LOG ---\n";
if (file_exists('agent_cron_log.txt')) {
    echo file_get_contents('agent_cron_log.txt');
} else {
    echo "agent_cron_log.txt not found\n";
}

echo "\n--- AGENT DAILY LOG ---\n";
if (file_exists('agent_daily_log.txt')) {
    echo file_get_contents('agent_daily_log.txt');
} else {
    echo "agent_daily_log.txt not found\n";
}

echo "\n--- HRD LAPORAN LOG ---\n";
if (file_exists('agent_hrd_laporan_log.txt')) {
    echo file_get_contents('agent_hrd_laporan_log.txt');
} else {
    echo "agent_hrd_laporan_log.txt not found\n";
}

echo "\n--- AUTOPILOT STATUS ---\n";
if (file_exists('autopilot_status.txt')) {
    echo file_get_contents('autopilot_status.txt');
} else {
    echo "autopilot_status.txt not found\n";
}
unlink(__FILE__);
