#!/bin/bash
#
# Before install  for Travis CI
#

#
# setup Mosquitto
# mosquitto is an old  version, need to trick to get activity log in a file
# with recent version, set log_dest and log_type in mosquitto.conf
#

# listen only on 127.0.01
sudo sed -i "s/#bind_address.*/bind_address 127.0.0.1/g" /etc/mosquitto/mosquitto.conf
# enable logging
sudo sed -i "s@log_dest.*@log_dest stderr@g" /etc/mosquitto/mosquitto.conf
sudo sh -c 'echo log_type error >> /etc/mosquitto/mosquitto.conf'
sudo sh -c 'echo log_type warning >> /etc/mosquitto/mosquitto.conf'
sudo sh -c 'echo log_type notice >> /etc/mosquitto/mosquitto.conf'
sudo sh -c 'echo log_type information >> /etc/mosquitto/mosquitto.conf'
sudo sh -c 'echo log_type debug >> /etc/mosquitto/mosquitto.conf'
sudo service mosquitto stop
sudo sh -c 'mosquitto -c /etc/mosquitto/mosquitto.conf 2>/tmp/mosquitto.log &'
