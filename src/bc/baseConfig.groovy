    
package bc;

class baseConfig {
  // Wait timeout in minutes
  public static final int WAIT_TIMEOUT = 10

  // Deployment Environment TAGs
  public static final String[] DEPLOYMENT_ENVIRONMENT_TAGS = ['dev', 'test', 'prod']

  // The name of the project namespace(s).
  public static final String  NAME_SPACE = '6b08a3'

  // Apps - Listed in the order they should be built
  public static final String[] BUILD_APPS = ['drupal', 'solr']

  // Apps - Listed in the order they should be deployed
  public static final String[] DEPLOY_APPS = ['drupal-db', 'drupal', 'solr', 'backup-mariadb']
}