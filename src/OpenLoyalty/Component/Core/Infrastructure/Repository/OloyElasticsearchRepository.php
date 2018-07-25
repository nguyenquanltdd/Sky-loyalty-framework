<?php
/**
 * Copyright © 2017 Divante, Inc. All rights reserved.
 * See LICENSE for license details.
 */
namespace OpenLoyalty\Component\Core\Infrastructure\Repository;

use Broadway\ReadModel\ElasticSearch\ElasticSearchRepository;
use Broadway\ReadModel\Repository;
use Broadway\Serializer\Serializer;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;

/**
 * Class OloyElasticsearchRepository.
 */
class OloyElasticsearchRepository extends ElasticSearchRepository implements Repository
{
    /** @var Client */
    protected $client;

    /** @var Serializer */
    protected $serializer;

    /** @var string */
    protected $index;

    /** @var string */
    protected $class;

    /** @var array */
    protected $notAnalyzedFields;

    /** @var array */
    protected $dynamicFields = [];

    /** @var int */
    private $maxResultWindowSize = 10000;

    /**
     * @param Client     $client
     * @param Serializer $serializer
     * @param string     $index
     * @param string     $class
     * @param array      $notAnalyzedFields
     */
    public function __construct(
        Client $client,
        Serializer $serializer,
        $index,
        $class,
        array $notAnalyzedFields = array()
    ) {
        parent::__construct($client, $serializer, $index, $class, $notAnalyzedFields);
        $this->client = $client;
        $this->serializer = $serializer;
        $this->index = $index;
        $this->class = $class;
        $this->notAnalyzedFields = $notAnalyzedFields;
    }

    /**
     * {@inheritdoc}
     */
    public function createIndex(): bool
    {
        $class = $this->class;

        $indexParams = array(
            'index' => $this->index,
        );
        if (count($this->notAnalyzedFields)) {
            $indexParams['body']['mappings']['properties'] = $this->createNotAnalyzedFieldsMapping($this->notAnalyzedFields);
        }
        $defaultDynamicFields = [
            [
                'email' => [
                    'match' => 'email',
                    'match_mapping_type' => 'string',
                    'mapping' => [
                        'type' => 'string',
                        'analyzer' => 'email',
                    ],
                ],
            ],
            [
                'someemail' => [
                    'match' => '*Email',
                    'match_mapping_type' => 'string',
                    'mapping' => [
                        'type' => 'string',
                        'analyzer' => 'email',
                    ],
                ],
            ],
            [
                'notanalyzed' => [
                    'match' => '*Id',
                    'match_mapping_type' => 'string',
                    'mapping' => [
                        'type' => 'string',
                        'index' => 'not_analyzed',
                    ],
                ],
            ],
            [
                'loyaltyCard' => [
                    'match' => 'loyaltyCardNumber',
                    'match_mapping_type' => 'string',
                    'mapping' => [
                        'type' => 'string',
                        'index' => 'not_analyzed',
                    ],
                ],
            ],
            [
                'postal' => [
                    'match' => 'postal',
                    'match_mapping_type' => 'string',
                    'mapping' => [
                        'type' => 'string',
                        'index' => 'not_analyzed',
                    ],
                ],
            ],
            [
                'phone' => [
                    'match' => 'phone',
                    'match_mapping_type' => 'string',
                    'mapping' => [
                        'type' => 'string',
                        'index' => 'not_analyzed',
                    ],
                ],
            ],
        ];
        $indexParams['body'] = array(
            'settings' => [
                'analysis' => [
                    'analyzer' => [
                        'email' => [
                            'tokenizer' => 'uax_url_email',
                            'filter' => ['lowercase'],
                        ],
                        'small_letters' => [
                            'tokenizer' => 'keyword',
                            'filter' => ['lowercase'],
                        ],
                    ],
                    'filter' => [
                        'translation' => [
                            'type' => 'nGram',
                            'min_gram' => 2,
                            'max_gram' => 100,
                        ],
                    ],
                ],
            ],
            'mappings' => array(
                $class => array(
                    '_source' => array(
                        'enabled' => true,
                    ),
                    'dynamic_templates' => array_merge($this->dynamicFields, $defaultDynamicFields),
                ),
            ),
        );

        $this->client->indices()->create($indexParams);
        $response = $this->client->cluster()->health(array(
            'index' => $this->index,
            'wait_for_status' => 'yellow',
            'timeout' => '5s',
        ));

        return isset($response['status']) && $response['status'] !== 'red';
    }

    /**
     * @param array  $params
     * @param bool   $exact
     * @param int    $page
     * @param int    $perPage
     * @param null   $sortField
     * @param string $direction
     *
     * @return array
     */
    public function findByParametersPaginated(
        array $params,
        $exact = true,
        $page = 1,
        $perPage = 10,
        $sortField = null,
        $direction = 'DESC'
    ): array {
        if ($page < 1) {
            $page = 1;
        }

        $filter = [];

        foreach ($params as $key => $value) {
            if (is_array($value) && isset($value['type'])) {
                if ($value['type'] == 'number') {
                    $filter[] = [
                        'term' => [
                            $key => floatval($value['value']),
                        ],
                    ];
                } elseif ($value['type'] == 'range') {
                    $filter[] = [
                        'range' => [
                            $key => $value['value'],
                        ],
                    ];
                } elseif ($value['type'] == 'exists') {
                    $filter[] = [
                        'exists' => [
                            'field' => $key,
                        ],
                    ];
                } elseif ($value['type'] == 'exact') {
                    $filter[] = [
                        'term' => [
                            $key => $value['value'],
                        ],
                    ];
                } elseif ($value['type'] == 'allow_null') {
                    $filter[] = [
                        'bool' => [
                            'should' => [
                                ['term' => [$key => $value['value']]],
                                ['missing' => ['field' => $key]],
                            ],
                        ],
                    ];
                } elseif ($value['type'] == 'multiple') {
                    $bool = ['should' => [], 'minimum_should_match' => 1];
                    foreach ($value['fields'] as $k => $v) {
                        if (!$exact) {
                            $bool['should'][] = ['wildcard' => [$k => '*'.$v.'*']];
                        } else {
                            $bool['should'][] = ['term' => [$k => '*'.$v.'*']];
                        }
                    }
                    $filter[] = ['bool' => $bool];
                } elseif ($value['type'] == 'multiple_all') {
                    $bool = ['must' => []];
                    foreach ($value['fields'] as $k => $v) {
                        if (!isset($value['exact']) || !$value['exact']) {
                            $bool['must'][] = ['wildcard' => [$k => '*'.$v.'*']];
                        } else {
                            $bool['must'][] = ['term' => [$k => $v]];
                        }
                    }
                    $filter[] = ['bool' => $bool];
                }
            } elseif (!$exact) {
                $filter[] = [
                    'wildcard' => [
                        $key => '*'.$value.'*',
                    ],
                ];
            } else {
                $filter[] = [
                    'term' => [
                        // term must not contain escaping chars as it search exact values
                        $key => str_replace('\\', '', $value),
                    ],
                ];
            }
        }

        if ($sortField) {
            $sort = [
                $sortField => ['order' => strtolower($direction), 'ignore_unmapped' => true],
            ];
        } else {
            $sort = null;
        }

        if (count($filter) > 0) {
            $query = array(
                'bool' => array(
                    'must' => $filter,
                ),
            );
        } else {
            $query = array(
                'filtered' => array(
                    'query' => array(
                        'match_all' => array(),
                    ),
                ),
            );
        }

        return $this->paginatedQuery($query, $perPage === null ? null : ($page - 1) * $perPage, $perPage, $sort);
    }

    /**
     * @param array $notAnalyzedFields
     *
     * @return array
     */
    private function createNotAnalyzedFieldsMapping(array $notAnalyzedFields)
    {
        $fields = array();

        foreach ($notAnalyzedFields as $field) {
            $fields[$field] = array(
                'type' => 'string',
                'index' => 'not_analyzed',
            );
        }

        return $fields;
    }

    /**
     * Deletes the index for this repository's ReadModel.
     *
     * @return True, if the index was successfully deleted
     */
    public function deleteIndex(): bool
    {
        $indexParams = array(
            'index' => $this->index,
            'timeout' => '5s',
        );

        $this->client->indices()->delete($indexParams);

        return true;
    }

    /**
     * @param array $params
     * @param bool  $exact
     *
     * @return array
     */
    public function findByParameters(array $params, $exact = true): array
    {
        $filter = [];
        foreach ($params as $key => $value) {
            if (is_array($value) && isset($value['type'])) {
                if ($value['type'] == 'number') {
                    $filter[] = [
                        'term' => [
                            $key => floatval($value['value']),
                        ],
                    ];
                } elseif ($value['type'] == 'range') {
                    $filter[] = [
                        'range' => [
                            $key => $value['value'],
                        ],
                    ];
                } elseif ($value['type'] == 'exists') {
                    $filter[] = [
                        'exists' => [
                            'field' => $key,
                        ],
                    ];
                } elseif ($value['type'] == 'allow_null') {
                    $filter[] = [
                        'bool' => [
                            'should' => [
                                ['term' => [$key => $value['value']]],
                                ['missing' => ['field' => $key]],
                            ],
                        ],
                    ];
                } elseif ($value['type'] == 'multiple') {
                    $bool = ['should' => [], 'minimum_should_match' => 1];
                    foreach ($value['fields'] as $k => $v) {
                        if (!$exact) {
                            $bool['should'][] = ['wildcard' => [$k => '*'.$v.'*']];
                        } else {
                            $bool['should'][] = ['term' => [$k => '*'.$v.'*']];
                        }
                    }
                    $filter[] = ['bool' => $bool];
                } elseif ($value['type'] == 'multiple_all') {
                    $bool = ['must' => []];
                    foreach ($value['fields'] as $k => $v) {
                        if (!isset($value['exact']) || !$value['exact']) {
                            $bool['must'][] = ['wildcard' => [$k => '*'.$v.'*']];
                        } else {
                            $bool['must'][] = ['term' => [$k => $v]];
                        }
                    }
                    $filter[] = ['bool' => $bool];
                }
            } elseif (!$exact) {
                $filter[] = [
                    'wildcard' => [
                        $key => '*'.$value.'*',
                    ],
                ];
            } else {
                $filter[] = [
                    'term' => [
                        // term must not contain escaping chars as it search exact values
                        $key => str_replace('\\', '', $value),
                    ],
                ];
            }
        }

        $query = array(
            'bool' => array(
                'must' => $filter,
            ),
        );

        return $this->query($query);
    }

    /**
     * @param array $params
     * @param bool  $exact
     *
     * @return int
     */
    public function countTotal(array $params = [], $exact = true): int
    {
        $filter = [];
        foreach ($params as $key => $value) {
            if (is_array($value) && isset($value['type'])) {
                if ($value['type'] == 'number') {
                    $filter[] = [
                        'term' => [
                            $key => floatval($value['value']),
                        ],
                    ];
                } elseif ($value['type'] == 'range') {
                    $filter[] = [
                        'range' => [
                            $key => $value['value'],
                        ],
                    ];
                } elseif ($value['type'] == 'exists') {
                    $filter[] = [
                        'exists' => [
                            'field' => $key,
                        ],
                    ];
                } elseif ($value['type'] == 'allow_null') {
                    $filter[] = [
                        'bool' => [
                            'should' => [
                                ['term' => [$key => $value['value']]],
                                ['missing' => ['field' => $key]],
                            ],
                        ],
                    ];
                } elseif ($value['type'] == 'multiple') {
                    $bool = ['should' => [], 'minimum_should_match' => 1];
                    foreach ($value['fields'] as $k => $v) {
                        if (!$exact) {
                            $bool['should'][] = ['wildcard' => [$k => '*'.$v.'*']];
                        } else {
                            $bool['should'][] = ['term' => [$k => '*'.$v.'*']];
                        }
                    }
                    $filter[] = ['bool' => $bool];
                } elseif ($value['type'] == 'multiple_all') {
                    $bool = ['must' => []];
                    foreach ($value['fields'] as $k => $v) {
                        if (!isset($value['exact']) || !$value['exact']) {
                            $bool['must'][] = ['wildcard' => [$k => '*'.$v.'*']];
                        } else {
                            $bool['must'][] = ['term' => [$k => $v]];
                        }
                    }
                    $filter[] = ['bool' => $bool];
                }
            } elseif (!$exact) {
                $filter[] = [
                    'wildcard' => [
                        $key => '*'.$value.'*',
                    ],
                ];
            } else {
                $filter[] = [
                    'term' => [
                        // term must not contain escaping chars as it search exact values
                        $key => str_replace('\\', '', $value),
                    ],
                ];
            }
        }

        if (count($filter) > 0) {
            $query = array(
                'bool' => array(
                    'must' => $filter,
                ),
            );
        } else {
            $query = array(
                'filtered' => array(
                    'query' => array(
                        'match_all' => array(),
                    ),
                ),
            );
        }

        return $this->count($query);
    }

    /**
     * @param array $query
     * @param int   $from
     * @param int   $size
     * @param null  $sort
     *
     * @return array
     */
    protected function paginatedQuery(array $query, $from = 0, $size = 500, $sort = null)
    {
        $query = array(
            'index' => $this->index,
            'body' => array(
                'query' => $query,
                'size' => $size === null ? $this->getMaxResultWindowSize() : $size,
                'from' => $from,
            ),
        );
        if ($sort) {
            $query['body']['sort'] = $sort;
        }

        return $this->searchAndDeserializeHits(
            $query
        );
    }

    /**
     * @param array $query
     *
     * @return array
     */
    protected function searchAndDeserializeHits(array $query)
    {
        try {
            $result = $this->client->search($query);
        } catch (Missing404Exception $e) {
            return array();
        }

        if (!array_key_exists('hits', $result)) {
            return array();
        }

        return $this->deserializeHits($result['hits']['hits']);
    }

    /**
     * @param array $hits
     *
     * @return array
     */
    protected function deserializeHits(array $hits)
    {
        return array_map(array($this, 'deserializeHit'), $hits);
    }

    /**
     * @param array $hit
     *
     * @return mixed
     */
    private function deserializeHit(array $hit)
    {
        return $this->serializer->deserialize(
            array(
                'class' => $hit['_type'],
                'payload' => $hit['_source'],
            )
        );
    }

    /**
     * @param array $query
     *
     * @return int
     */
    protected function count(array $query)
    {
        $query = array(
            'index' => $this->index,
            'body' => array(
                'query' => $query,
            ),
        );

        try {
            $result = $this->client->count($query);
        } catch (Missing404Exception $e) {
            return 0;
        }

        if (!array_key_exists('count', $result)) {
            return 0;
        }

        return $result['count'];
    }

    /**
     * {@inheritdoc}
     */
    protected function search(array $query, array $facets = array(), int $size = 500): array
    {
        if (null === $size) {
            $size = $this->getMaxResultWindowSize();
        }

        try {
            return $this->client->search(array(
                'index' => $this->index,
                'body' => array(
                    'query' => $query,
                    'facets' => $facets,
                ),
                'size' => $size,
            ));
        } catch (Missing404Exception $e) {
            return array();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function query(array $query)
    {
        return $this->searchAndDeserializeHits(
            array(
                'index' => $this->index,
                'body' => array(
                    'query' => $query,
                ),
                'size' => $this->getMaxResultWindowSize(),
            )
        );
    }

    /**
     * @return mixed
     */
    public function getMaxResultWindowSize()
    {
        return $this->maxResultWindowSize;
    }

    /**
     * @param mixed $maxResultWindowSize
     */
    public function setMaxResultWindowSize($maxResultWindowSize)
    {
        $this->maxResultWindowSize = $maxResultWindowSize;
    }
}
