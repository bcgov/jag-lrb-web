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
profileRoot=$( dirname "$0" )/config/
profileEnv=${profileRoot}/${DEPLOYMENT_ENV_NAME}
profileTmp=$( dirname "$0" )/config/${PROFILE}/tmp
mkdir -p ${profileTmp}
cp -f ${profileRoot}/* ${profileTmp} 2>/dev/null
cp -f ${profileEnv}/* ${profileTmp} 2>/dev/null

# Generate the config map ...
DRUPAL_CONFIG_SOURCE_PATH=${profileTmp}
CONFIGMAP_OUTPUT_FILE=${DRUPAL_CONFIG_MAP_NAME}-configmap_DeploymentConfig.json
printStatusMsg "Generating ConfigMap; ${DRUPAL_CONFIG_MAP_NAME} ..."
generateConfigMap "${DRUPAL_CONFIG_MAP_NAME}" "${DRUPAL_CONFIG_SOURCE_PATH}" "${OUTPUT_FORMAT}" "${CONFIGMAP_OUTPUT_FILE}"

# Remove temporary configuration directory and files ....
rm -rf ${profileTmp}

unset SPECIALDEPLOYPARMS
echo ${SPECIALDEPLOYPARMS}