<?php

namespace CSSoft\Core\Model\ComponentList\Loader;

class Remote extends AbstractLoader
{
    const XML_USE_HTTPS_PATH = 'cssoft_core/modules/use_https';
    const XML_FEED_URL_PATH  = 'cssoft_core/modules/url';

    const RESPONSE_CACHE_KEY = 'cssoft_components_remote_response';

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Magento\Framework\HTTP\ClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cache;

    /**
     * @param \CSSoft\Core\Helper\Component                     $componentHelper
     * @param \Psr\Log\LoggerInterface                           $logger
     * @param \Magento\Framework\App\RequestInterface            $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Json\Helper\Data                $jsonHelper
     * @param \Magento\Framework\HTTP\ClientFactory              $httpClientFactory
     */
    public function __construct(
        \CSSoft\Core\Helper\Component $componentHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\HTTP\ClientFactory $httpClientFactory,
        \Magento\Framework\App\CacheInterface $cache
    ) {
        parent::__construct($componentHelper, $logger);
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->jsonHelper = $jsonHelper;
        $this->httpClientFactory = $httpClientFactory;
        $this->cache = $cache;
    }

    public function getMapping()
    {
        return [
            'description' => 'description',
            'keywords' => 'keywords',
            'name' => 'name',
            'version' => 'latest_version',
            'type' => 'type',
            'time' => 'release_date',
            'extra.cssoft.links.store' => 'link',
            'extra.cssoft.links.docs' => 'docs_link',
            'extra.cssoft.links.download' => 'download_link',
            'extra.cssoft.links.changelog' => 'changelog_link',
            'extra.cssoft.links.marketplace' => 'marketplace_link',
            'extra.cssoft.links.identity_key' => 'identity_key_link',
            'extra.cssoft.purchase_code' => 'purchase_code',
        ];
    }

    /**
     * Retrieve component names and configs from remote satis repository
     *
     * @return \Traversable
     */
    public function getComponentsInfo()
    {
        try {
            if (!$responseBody = $this->cache->load(self::RESPONSE_CACHE_KEY)) {
                $responseBody = $this->fetch($this->getFeedUrl());
                $this->cache->save($responseBody, self::RESPONSE_CACHE_KEY, [], 86400);
            }
            $response = $this->jsonHelper->jsonDecode($responseBody);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $response = [];
            // CSSoft_Subscription will be added below - used by
            // subscription activation module
        }

        if (!is_array($response)) {
            $response = [];
        }

        $modules = [];
        if (!empty($response['packages'])) {
            foreach ($response['packages'] as $packageName => $info) {
                $versions = array_keys($info);
                $latestVersion = array_reduce($versions, function ($carry, $item) {
                    if (version_compare($carry, $item) === -1) {
                        $carry = $item;
                    }
                    return $carry;
                }, $versions[0] ?? 0);
                if (!empty($info[$latestVersion]['type']) &&
                    $info[$latestVersion]['type'] === 'metapackage') {

                    continue;
                }
                $modules[$packageName] = $info[$latestVersion];

                if (isset($info['dev-master']['extra']['cssoft'])) {
                    $modules[$packageName]['extra']['cssoft'] =
                        $info['dev-master']['extra']['cssoft'];
                }
            }
        }

        $modules['cssoft/subscription'] = [
            'name'          => 'cssoft/subscription',
            'type'          => 'subscription-plan',
            'description'   => 'Cssoftsolutons Modules Subscription',
            'version'       => '',
            'extra' => [
                'cssoft' => [
                    'links' => [
                        'store' => 'https://cssoftsolutions.com',
                        'download' => 'https://cssoftsolutions.com/subscription/customer/products/',
                        'identity_key' => 'https://cssoftsolutions.com/license/customer/identity/'
                    ]
                ]
            ]
        ];

        return $modules;
    }

    /**
     * Make a http request and return response body
     *
     * @param  string $url
     * @return string
     */
    protected function fetch($url)
    {
        $client = $this->httpClientFactory->create();
        $client->setOption(CURLOPT_FOLLOWLOCATION, true);
        $client->setOption(CURLOPT_MAXREDIRS, 5);
        $client->setTimeout(30);
        $client->get($url);
        return $client->getBody();
    }

    /**
     * Get feed url from satis repository.
     *
     * To do that we send a request to http://docs.cssoftsolutions.com/packages/packages.json,
     * which returns actual packages list url: http://docs.cssoftsolutions.com/packages/include/all${sha1}.json
     *
     * @return string
     */
    protected function getFeedUrl()
    {
        $useHttps = $this->scopeConfig->getValue(
            self::XML_USE_HTTPS_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $url = $this->scopeConfig->getValue(
            self::XML_FEED_URL_PATH,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        // http://docs.cssoftsolutions.com/packages/packages.json
        $url = ($useHttps ? 'https://' : 'http://') . $url;

        $response = $this->fetch($url . '/packages.json');
        $response = $this->jsonHelper->jsonDecode($response);
        if (!is_array($response) || !isset($response['includes'])) {
            return false;
        }

        // http://docs.cssoftsolutions.com/packages/include/all${sha1}.json
        return $url . '/' . key($response['includes']);
    }
}
