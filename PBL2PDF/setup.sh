
apt-get update
apt-get install -y wget gnupg2 curl ca-certificates
export DEBIAN_FRONTEND=noninteractive \
&& export TZ=Europe/Paris \
&& apt-get update \
&& apt-get install -y fonts-dejavu

# Update package list and upgrade any outdated packages
apt-get update && apt-get upgrade -y

# Install required dependencies for wkhtmltopdf
apt-get install -y libfontconfig1 libjpeg-turbo8 libxrender1 fontconfig xvfb

# Download and install wkhtmltopdf package for Ubuntu 22.04
wget https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-2/wkhtmltox_0.12.6.1-2.jammy_amd64.deb
dpkg -i wkhtmltox_0.12.6.1-2.jammy_amd64.deb

# Verify installation
wkhtmltopdf --version


