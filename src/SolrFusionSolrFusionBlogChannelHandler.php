<?php

namespace Drupal\solr_fusion;

/**
 * SolrFusion Blog Channel Handler.
 */
class SolrFusionSolrFusionBlogChannelHandler {

  /**
   * Handles blog channels.
   *
   * @param string $channel
   *   The channel to handle.
   *
   * @return string
   *   The added filter.
   */
  public static function handle(string $channel): string {
    switch ($channel) {
      case 'solr-fusion-news':
        return '&fq=' . urlencode('taxonomy_blog_post_category:("Awards" OR "Corporate" OR "Customer success" OR "Partners")');

      case 'solr-fusion-summit':
        return '&fq=' . urlencode('taxonomy_blog_post_category:("Events")');

      case 'cloud-native-computing':
        return '&fq=' . urlencode('taxonomy_blog_post_category:("Application integration and development" OR "Containers" OR "Middleware" OR "Mobile" OR "PaaS" OR "xPaaS") OR taxonomy_product:("3scale API Management" OR "Middleware" OR "OpenShift") OR taxonomy_product_line:("Cloud computing" OR "Middleware") OR taxonomy_solution:("Application integration and development" OR "Platform-as-a-Service") OR taxonomy_topic:("APIs" OR "Containers" OR "DevOps") OR taxonomy_business_challenge:("Cloud-native development")');

      case 'security':
        return '&fq=' . urlencode('taxonomy_blog_post_category:("Security")');

      case 'hybrid-cloud-infrastructure':
        return '&fq=' . urlencode('taxonomy_blog_post_category:("Hybrid cloud" OR "IaaS" OR "Infrastructure" OR "PaaS" OR "Virtualization") OR taxonomy_product:("Cloud Access" OR "Cloud Suite" OR "CloudForms" OR "OpenStack Platform" OR "Virtualization") OR taxonomy_product_line:("Cloud computing" OR "Virtualization") OR taxonomy_solution:("Infrastructure-as-a-Service" OR "Platform-as-a-Service") OR taxonomy_topic:("Cloud" OR "Management" OR "OpenStack" OR "Virtualization") OR taxonomy_business_challenge:("Hybrid cloud infrastructure")');

      case 'management-and-automation':
        return '&fq=' . urlencode('taxonomy_blog_post_category:("Automation") OR taxonomy_product:("Advanced Cluster Management" OR "Ansible Automation Platform" OR "CloudForms" OR "Insights" OR "Process Automation Manager" OR "Satellite") OR taxonomy_product_line:("Management") OR taxonomy_topic:("Automation" OR "Process") OR taxonomy_business_challenge:("IT automation")');

      case 'solr-fusion-open-source-program-office':
        return '&fq=' . urlencode('taxonomy_blog_post_category:("Open Source Program Office")');

      case 'digital-transformation':
        return '&fq=' . urlencode('taxonomy_blog_post_category:("Automation" OR "Edge Computing") OR taxonomy_topic:("DevOps") OR taxonomy_business_challenge:("Digital transformation")');

      case 'edge-computing':
        return '&fq=' . urlencode('taxonomy_blog_post_category:("Edge Computing")');

      case 'financial-services':
        return '&fq=' . urlencode('taxonomy_blog_post_category:("Financial services") OR taxonomy_industry:("Financial services")');

      case 'life-solr-fusion':
        return '&fq=' . urlencode('taxonomy_blog_post_category:("Community" OR "Friday Five" OR "Open Brand" OR "Open Studio" OR "Shares") OR taxonomy_topic:("Culture" OR "Open source" OR "Open source communities")');

      case 'open-outlook':
        return '&fq=' . urlencode('taxonomy_blog_post_category:("Open Outlook")');

      case 'solr-fusion-middleware':
        return '&fq=' . urlencode('taxonomy_blog_post_category:("Middleware") OR taxonomy_product:("CodeReady" OR "Data Grid" OR "JBoss Enterprise Application Platform" OR "JBoss Web Server" OR "Middleware" OR "Runtimes")');

      case 'solr-fusion-events':
        return '&fq=' . urlencode('taxonomy_blog_post_category:("Events") OR taxonomy_topic:("Events") OR taxonomy_region:("Global")');

      case 'solr-fusion-open-studio':
        return '&fq=' . urlencode('taxonomy_blog_post_category:("Open OutlookStudio")');

      case 'solr-fusion-services-speak':

        return '&fq=' . urlencode('taxonomy_blog_post_category:("Consulting" OR "Services" OR "Training") OR taxonomy_services:("Consulting" OR "Training" OR "Certification")');

      case 'solr-fusion-technical-account-managers-blog':
        return '&fq=' . urlencode('taxonomy_blog_post_category:("Technical Account Managers")');

      case 'telecommunications':
        return '&fq=' . urlencode('taxonomy_blog_post_category:("Telecommunications") OR taxonomy_industry:("Telecommunications")');

      case 'vertical-industries-blog':
        return '&fq=' . urlencode('taxonomy_blog_post_category:("Telecommunications" OR "Financial services") OR taxonomy_industry:("Financial services" OR "Government" OR "Healthcare" OR "Oil & gas" OR "Telecommunications")');

      default:
        return '';
    }
  }

}
