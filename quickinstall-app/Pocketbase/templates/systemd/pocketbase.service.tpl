Unit]
Description=Pocketbase service for %app_name%
After=network.target

[Service]
Type=simple
User=%user%
WorkingDirectory=%app_dir%
ExecStart=%app_dir%/pocketbase serve --http=127.0.0.1:%pocketbase_port%
Restart=on-failure

[Install]
WantedBy=multi-user.target
