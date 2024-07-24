<?php

namespace Drupal\solr_fusion\Solr;

use Drupal\Component\Utility\Html;
use Solarium\Core\Query\AbstractQuery;
use Solarium\QueryType\Select\Query\Query;

/**
 * A select query.
 */
class SelectQuery extends Query implements SolrFusionSolrQueryInterface {

  /**
   * The parameters that get sent to Solr.
   *
   * @var array
   *   An array of parameters.
   */
  protected $params;

  /**
   * The name of the query.
   *
   * @var string
   *   The name of the query.
   */
  private string $name;

  /**
   * The sort string.
   *
   * @var string
   *   A sort string.
   */
  private string $sortString;

  /**
   * Constructor.
   *
   * @param array|null $options
   *   Options.
   * @param array $params
   *   Parameters.
   */
  public function __construct(array $options = NULL, array $params = []) {
    parent::__construct($options);
    $this->addParams($params);
  }

  /**
   * {@inheritDoc}
   */
  public function addFacets(string $language, string $configuration, $facets) {
    $set = $this->getFacetSet();

    // ITMKTGCMS-8291 - Only show language translations.
    $set->createFacetField('Content_Language')
      ->setField('Content_Language')
      ->setMinCount(1)
      ->setLimit(50)
      ->setKey($language);

    $set->createFacetField('content_language')
      ->setField('content_language')
      ->setMinCount(1)
      ->setLimit(50)
      ->setKey($language);

    // The facets for the solr query.
    $items = explode('|', $configuration);

    // Add the facets for the solr query.
    foreach ($facets as $facet) {
      if (in_array($facet->get('field'), $items)) {
        $set->createFacetField($facet->get('label'))
          ->setField($facet->get('field'))
          ->setMinCount($facet->get('min_count'))
          ->setLimit($facet->get('limit'));
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function defaultParameters(bool $addToQuery = FALSE): array {
    $params = [
      'fl' => $this->getDefaultFl(),
      'mm' => 1,
      'rows' => 10,
      'pf' => 'content^2.0',
      'ps' => 15,
      'hl' => 'true',
      'hl.fl' => 'content',
      'hl.snippets' => 3,
      'hl.mergeContiguous' => 'true',
      'f.content.hl.alternateField' => 'teaser',
      'f.content.hl.maxAlternateFieldLength' => 256,
    ];

    if ($addToQuery) {
      $this->addParams($params);
    }

    return $params;
  }

  /**
   * {@inheritDoc}
   */
  public function getDefaultFl(): array {
    return [
      'id',
      'entity_id',
      'entity_type',
      'bundle',
      'bundle_name',
      'label',
      'ss_language',
      'score',
      '[elevated]',
      'is_comment_count',
      'ds_created',
      'ds_changed',
      'source',
      'path',
      'url',
      'is_uid',
      'tos_name',
      'dc_title',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function addParams(Array $params): static {
    foreach ($params as $name => $value) {
      $this->addParam($name, $value);
    }
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function addFilters(array $filters, bool $useOrJoin = FALSE): array {
    $unfiltered = [];
    $qAlt = [];
    foreach ($filters as $filter) {
      $qAlt[] = '(' . $filter . ')';
      $tokens = explode(':', $filter);
      $unfiltered[$tokens[0]][] = $tokens[1];
    }

    foreach ($unfiltered as $key => $value) {
      // Escape keys, but allow + and - to be pass through as it's an allowable
      // string in 'fq'.  Value will contain double quotes, must use
      // htmlspecialchars with ENT_NOQUOTES to allow double quotes to work.
      $key = Html::escape($key);
      if (count($unfiltered[$key]) > 1) {

        if (!$useOrJoin) {
          $values = implode(' ' . $this::QUERY_OPERATOR_AND . ' ', array_map(function ($key_value) {
            return htmlspecialchars($key_value, ENT_NOQUOTES);
          }, $value));
        }
        else {
          $values = implode(' ' . $this::QUERY_OPERATOR_OR . ' ', array_map(function ($key_value) {
            return htmlspecialchars($key_value, ENT_NOQUOTES);
          }, $value));
        }

        $this->createFilterQuery($key)->setQuery($key . ':(' . $values . ')');
      }
      else {
        $this->createFilterQuery($key)->setQuery($key . ':' . htmlspecialchars($value[0], ENT_NOQUOTES));
      }
    }

    return $qAlt;
  }

  /**
   * Single value parameters.
   *
   * @var bool[]
   *   Single value parameters.
   */
  protected $singleValueParams = [
    // http://wiki.apache.org/solr/SearchHandler#q.
    'q' => TRUE,
    // http://wiki.apache.org/solr/SearchHandler#q.op.
    'q.op' => TRUE,
    // http://wiki.apache.org/solr/SearchHandler#q.
    'q.alt' => TRUE,
    'df' => TRUE,
    'qt' => TRUE,
    'defType' => TRUE,
    'timeAllowed' => TRUE,
    'omitHeader' => TRUE,
    'debugQuery' => TRUE,
    'start' => TRUE,
    'rows' => TRUE,
    'stats' => TRUE,
    'facet' => TRUE,
    'facet.prefix' => TRUE,
    'facet.limit' => TRUE,
    'facet.offset' => TRUE,
    'facet.mincount' => TRUE,
    'facet.missing' => TRUE,
    'facet.method' => TRUE,
    'facet.enum.cache.minDf' => TRUE,
    'facet.date.start' => TRUE,
    'facet.date.end' => TRUE,
    'facet.date.gap' => TRUE,
    'facet.date.hardend' => TRUE,
    'facet.date.other' => TRUE,
    'facet.date.include' => TRUE,
    'hl' => TRUE,
    'hl.snippets' => TRUE,
    'hl.fragsize' => TRUE,
    'hl.mergeContiguous' => TRUE,
    'hl.requireFieldMatch' => TRUE,
    'hl.maxAnalyzedChars' => TRUE,
    'hl.alternateField' => TRUE,
    'hl.maxAlternateFieldLength' => TRUE,
    'hl.formatter' => TRUE,
    'hl.simple.pre/hl.simple.post' => TRUE,
    'hl.fragmenter' => TRUE,
    'hl.fragListBuilder' => TRUE,
    'hl.fragmentsBuilder' => TRUE,
    'hl.useFastVectorHighlighter' => TRUE,
    'hl.usePhraseHighlighter' => TRUE,
    'hl.highlightMultiTerm' => TRUE,
    'hl.regex.slop' => TRUE,
    'hl.regex.pattern' => TRUE,
    'hl.regex.maxAnalyzedChars' => TRUE,
    'mm' => TRUE,
    'spellcheck' => TRUE,
  ];

  /**
   * {@inheritDoc}
   */
  public function setResultClass(string $classname): AbstractQuery {
    parent::setOption('resultclass', $classname);
    return $this;
  }

  /**
   * Build the qf parameter.
   *
   * @param bool|string[] $queryFields
   *   The query fields to use.
   * @param string $language
   *   The language.
   *
   * @return array
   *   An array of qf values.
   */
  public function buildQf(array|bool $queryFields, string $language = 'en'): array {
    $qf = [];
    if (empty($queryFields)) {
      return $qf;
    }

    foreach ($queryFields as $qField) {
      if (!empty($qField)) {
        $qf[] = $qField;
        if ($qField == 'content_language') {
          $qf[] = 'content_' . $language;
        }
      }
    }
    return $qf;
  }

}
