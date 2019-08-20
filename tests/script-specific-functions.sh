#!/bin/sh

install_plugin() {
    install_mosquitto
    install_fusioninventory
}

init_plugin() {
    init_fusioninventory
}

install_mosquitto() {
    # setup Mosquitto
    # mosquitto is an old  version, need to trick to get activity log in a file
    # with recent version, set log_dest and log_type in mosquitto.conf
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
}

install_fusioninventory() {
    echo Installing Fusion Inventory
    pwd
    mkdir ../fusioninventory && git clone --depth=35 $FI_SOURCE -b $FI_BRANCH ../fusioninventory
    # patch Fusion Inventory when needed
    echo Patching Fusion Inventory
    cd ../fusioninventory
    if [[ $FI_BRANCH == "master" ]] ; then patch $PATCH_ARGS < ../flyvemdm/tests/patches/fusioninventory/fi-raise-max-version.patch; fi
    if [[ $FI_BRANCH == "master" ]] ; then patch $PATCH_ARGS < ../flyvemdm/tests/patches/fusioninventory/compat-glpi-9-3-2.diff; fi
    if [[ $FI_BRANCH == "glpi9.3" ]] ; then patch $PATCH_ARGS < ../flyvemdm/tests/patches/fusioninventory/compat-glpi-9-3-2.diff; fi
    echo Patching Flyve MDM
    cd ../flyvemdm
    if [[ $GLPI_BRANCH == "master" ]] ; then patch $PATCH_ARGS < tests/patches/allow-test-on-master-branch.patch; fi
}

init_fusioninventory() {
    echo Initializing Fusion Inventory
    pwd
    php ./tests/install_fusioninventory.php --as-user glpi
}
