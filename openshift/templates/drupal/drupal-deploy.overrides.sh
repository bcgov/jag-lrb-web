#!/bin/bash
_includeFile=$(type -p overrides.inc)
if [ ! -z ${_includeFile} ]; then
  . ${_includeFile}
else
  _red='\033[0;31m'; _yellow='\033[1;33m'; _nc='\033[0m'; echo -e \\n"${_red}overrides.inc could not be found on the path.${_nc}\n${_yellow}Please ensure the openshift-developer-tools are installed on and registered on your path.${_nc}\n${_yellow}https://github.com/BCDevOps/openshift-developer-tools${_nc}"; exit 1;
fi

OUTPUT_FORMAT=json

# Generate application config map
# - To include all of the files in the application instance's profile directory.
# Injected by genDepls.sh
# - DRUPAL_CONFIG_MAP_NAME

# Combine the profile's default config files with its environment specific config files before generating the config map ...
configRoot=$( dirname "$0" )/config/
profileEnv=${configRoot}/${DEPLOYMENT_ENV_NAME}
profileTmp=$( dirname "$0" )/config/tmp
mkdir -p ${profileTmp}
cp -f ${configRoot}/.* ${configRoot}/* ${profileTmp} 2>/dev/null
cp -f ${profileEnv}/.* ${profileEnv}/* ${profileTmp} 2>/dev/null

# Generate the config map ...
DRUPAL_CONFIG_SOURCE_PATH=${profileTmp}
CONFIGMAP_OUTPUT_FILE=${DRUPAL_CONFIG_MAP_NAME}-configmap_DeploymentConfig.json
printStatusMsg "Generating ConfigMap; ${DRUPAL_CONFIG_MAP_NAME} ..."
generateConfigMap "${DRUPAL_CONFIG_MAP_NAME}" "${DRUPAL_CONFIG_SOURCE_PATH}" "${OUTPUT_FORMAT}" "${CONFIGMAP_OUTPUT_FILE}"

# Remove temporary configuration directory and files ....
rm -rf ${profileTmp}
unset SPECIALDEPLOYPARMS

if createOperation; then
  # Get the settings for delivering user feedback to the business
  readParameter "HTTP_AUTH_USER - Please provide the username for setting up http authentication.  The default is a blank string (authentication disabled)." HTTP_AUTH_USER "false"
  readParameter "HTTP_AUTH_PASSWORD - Please provide the password for setting up http authentication.  The default is a blank string (authentication disabled)." HTTP_AUTH_PASSWORD "false"
else
  # Secrets are removed from the configurations during update operations ...
  printStatusMsg "Update operation detected ...Skipping the prompts for HTTP_AUTH_USER and HTTP_AUTH_PASSWORD environment variables ...\n"
  writeParameter "HTTP_AUTH_USER" $(getSecret "${NAME}-http-auth" "user") "false"
  writeParameter "HTTP_AUTH_PASSWORD" $(getSecret "${NAME}-http-auth" "password") "false"
fi

SPECIALDEPLOYPARMS="--param-file=${_overrideParamFile}"
echo ${SPECIALDEPLOYPARMS}