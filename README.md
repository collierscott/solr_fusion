# Configuration and ENV variables needed

The following environment variables need to be set in order for listings to work.

SOLR_USERNAME
SOLR_PASSWORD
FUSION_USERNAME
FUSION_PASSWORD

Locally, this can be put into your `settings.local.php` file.
```
putenv("SOLR_USERNAME=<username>");
putenv("SOLR_PASSWORD=<password>");
putenv("FUSION_USERNAME=<username>");
putenv("FUSION_PASSWORD=<password>");
```
