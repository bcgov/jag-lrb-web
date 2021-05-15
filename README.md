# Labour Relations Board Website Rebuild

![img](https://img.shields.io/badge/Lifecycle-Experimental-339999)

Build and deployment configurations to stand-up a Drupal instance, backed by a MariaDB database, in OpenShift.

The project uses the [openshift-developer-tools](https://github.com/BCDevOps/openshift-developer-tools) for processing the templates and provisioning the builds/deployment configurations.

## Running Locally

To run the project in a local development environment, a Docker setup has been provided.

From the [docker](./docker) folder:

1. Execute `./manage build` to prepare the container images.

2. Execute `./manage start` to run the containers.

The Drupal site will be served on http://localhost:8080.

To locally test changes to the Drupal configuration, edit the files in [docker/drupal/config](./docker/drupal/config).
To locally test changes to the SOLR configuration, edit the files in [solr/cores/lrb/conf](./solr/cores/lrb/conf).

To apply the changes, rebuild and restart the services using the `manage` commands above.

All of the data saved to the app filesystem and database will be persisted until `./manage rm` is executed: this command is destructive, and will clean all of the docker containers/volumes.

## Updating OpenShift

Most changes to the OpenShift environment(s) can be applied simply by merging a pull request that includes changes to the [app](./app) folder (for Drupal) or the [solr/cores/lrb/conf](./solr/cores/lrb/conf) for SOLR. The `build-pipeline` will be triggered automatically on each merge event and, once the builds are completed, the changes will be deployed to the `dev` environment.

To promote changes from `dev` to `test` run the `deploy-to-test-pipeline`.

To promote changes from `test` to `prod` run the `deploy-to-prod-pipeline`.

Pipelines can be accessed in the `tools` namespace in OpenShift, direct link [here](https://console.apps.silver.devops.gov.bc.ca/k8s/ns/6b08a3-tools/buildconfigs).

### Deployment Specific Settings

The Drupal app has a couple of settings files that are customizable on a per-deployment basis, they can be found in [openshift/templates/drupal/config](./openshift/templates/drupal/config).

- `php.ini` is shared across all of the environments (`dev/test/prod`).

- `settings.php` is customizable on a per-environment basis.

- `.htaccess` is customizable on a per-environment basis.

To apply changes to these files in OpenShift:

1. Update the file that needs to be updated.

2. From within the [openshift](./openshift) folder, use the `manage` script to updathe the deployed files, e.g.: `./manage -e dev deploy` to update the `dev` environment.

**Please Note:** the `manage` script extends functionality provided by the [openshift-developer-tools](https://github.com/BCDevOps/openshift-developer-tools) and therefore requires them to be installed and on the path in order to work properly.

### HTTP Basic Auth

HTTP Basic Authentication is configured by adding the relevant section to the `.htaccess` file.

The user credentials are stored in a secret named `drupal-http-auth` in each namespace (dev/test/prod).

Please note that to enable a user for basic http authentication, the secret must be populated, otherwise the `.htpasswd` file will remain empty and it will not be possible to access the website.
