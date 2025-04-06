# ✅ Use systemd Service (Best for Production & Uptime)
It ensures:

- Your script starts automatically on boot.
- It restarts if it crashes.
- You can control it easily with systemctl.

## 🔧 Step-by-Step Guide

- 🛠️ Create a systemd service file
    ```bash
    vi /etc/systemd/system/order-consumer.service
- Paste this config:
    ```bash
    [Unit]
    Description=Quantumleap RabbitMQ Order Consumer
    After=network.target

    [Service]
    ExecStart=/usr/bin/php /var/www/emr-dev/library/RabbitMQ/order_consumer.php
    WorkingDirectory=/var/www/emr-dev
    StandardOutput=append:/var/log/order-consumer.log
    StandardError=append:/var/log/order-consumer-error.log
    Restart=always
    User=www-data
    Environment=OPENEMR_SITE=default

    [Install]
    WantedBy=multi-user.target

### ✅ Make sure:
- /usr/bin/php is the correct PHP CLI path (which php)
- User=www-data or whatever user your webserver runs as.
- Logging goes to /var/log/order-consumer.log.

##  🔄 Reload systemd & start the service
```bash
sudo systemctl daemon-reload
sudo systemctl enable order-consumer
sudo systemctl start order-consumer
```
## ✅ Check service status
```bash 
sudo systemctl status order-consumer
```
##  📄 View logs
```bash
tail -f /var/log/order-consumer.log
```
- Error logs:
    ```bash
    tail -f /var/log/order-consumer-error.log
    ```
### Others
```bash
sudo systemctl restart order-consumer
sudo systemctl stop order-consumer
```