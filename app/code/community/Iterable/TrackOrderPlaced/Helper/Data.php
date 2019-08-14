<?php

class Iterable_TrackOrderPlaced_Helper_Data extends Mage_Core_Helper_Abstract {
    
    const XML_PATH_ITERABLE_API_KEY = 'api_options/api_key_options/api_key';
    const XML_PATH_ENABLED_EVENTS = 'advanced_options/tracking_options/enabled_events';

    private function getDecodedMagentoApiToken() {
        $magentoApiKey = Mage::getStoreConfig(self::XML_PATH_ITERABLE_API_KEY);
        return json_decode(base64_decode($magentoApiKey));
    }

    private function getIterableApiToken() {
        $apiKeyJson = $this->getDecodedMagentoApiToken();
        if ($apiKeyJson == NULL) {
            return NULL;
        }
        return $apiKeyJson->t;
    }
   
    public function getNewsletterEmailListId() {
        $apiKeyJson = $this->getDecodedMagentoApiToken();
        if ($apiKeyJson == NULL) {
            return NULL;
        }
        return $apiKeyJson->n;
    }

    public function getAccountEmailListId() {
        $apiKeyJson = $this->getDecodedMagentoApiToken();
        if ($apiKeyJson == NULL) {
            return NULL;
        }
        return $apiKeyJson->u;
    }

    private function callIterableApi($event, $endpoint, $params) {
        $eventsToTrack = Mage::getStoreConfig(self::XML_PATH_ENABLED_EVENTS);
        $eventsToTrack = explode(",", $eventsToTrack);
        if (!in_array($event, $eventsToTrack)) {
            // TODO - maybe run this before gathering data about the cart
            return;
        }
        $apiKey = $this->getIterableApiToken();
        if ($apiKey == NULL) {
            return;
        }
        $url = "https://api.iterable.com/{$endpoint}?api_key={$apiKey}";
        try {
            $client = new Zend_Http_Client($url);
        } catch(Exception $e) {
            Mage::log("Warning: unable to create http client with url {$url} ({$e->getMessage()})");
            return;
        }
        $client->setMethod(Zend_Http_Client::POST);
        // $client->setHeaders('Content-Type', 'application/json'); 
        $json = json_encode($params);
        $client->setRawData($json, 'application/json');
        try {
            $response = $client->request();
            $status = $response->getStatus();
            if ($status != 200) {
                Mage::log("Iterable Tracker: Unable to track event at {$endpoint} with params {$json}; got status {$status} with body {$response->getBody()}");
            }
        } catch(Exception $e) {
            Mage::log("Warning: unable to send event at {$endpoint} with params {$json} to Iterable ({$e->getMessage()})");
        }
    }

    public function updateUser($email, $dataFields) {
        $endpoint = '/api/users/update';
        $params = array(
            'email' => $email
        );
        if (!empty($dataFields)) {
            $params['dataFields'] = $dataFields;
        }
        $this->callIterableApi(Iterable_TrackOrderPlaced_Model_TrackingEventTypes::EVENT_TYPE_USER, $endpoint, $params);
    }
    
    public function subscribeEmailToList($email, $listId, $dataFields=array(), $resubscribe=False) {
        $endpoint = '/api/lists/subscribe';
        $params = array(
            'listId' => $listId,
            'subscribers' => array(
                array(
                    'email' => $email
                )
            ),
            'resubscribe' => $resubscribe
        );
        if (!empty($dataFields)) {
            $params['subscribers'][0]['dataFields'] = $dataFields;
        }
        $this->callIterableApi(Iterable_TrackOrderPlaced_Model_TrackingEventTypes::EVENT_TYPE_NEWSLETTER_SUBSCRIBE, $endpoint, $params);
    }
    
    public function unsubscribeEmailFromList($email, $listId) {
        $endpoint = '/api/lists/unsubscribe';
        $params = array(
            'listId' => $listId,
            'subscribers' => array(
                array(
                    'email' => $email
                )
            )
            // 'campaignId' => iterableCid cookie?
        );
        $this->callIterableApi(Iterable_TrackOrderPlaced_Model_TrackingEventTypes::EVENT_TYPE_NEWSLETTER_UNSUBSCRIBE, $endpoint, $params);
    }

    /*
    public function track($event, $email, $dataFields=array()) {
        $endpoint = '/api/events/track';
        $params = array(
            'email' => $email,
            'eventName' => $event
        );            
        if (!empty($dataFields)) {
            $params['dataFields'] = $dataFields;
        }
        $this->callIterableApi($event, $endpoint, $params);
    }
    */

    public function updateCart($email, $items, $dataFields=array()) {
        $endpoint = '/api/commerce/updateCart';
        $params = array(
            'user' => array(
                'email' => $email
            ),
            'items' => $items
        );
        if (!empty($dataFields)) {
            $params['user']['dataFields'] = $dataFields;
        }
        $this->callIterableApi(Iterable_TrackOrderPlaced_Model_TrackingEventTypes::EVENT_TYPE_CART_UPDATED, $endpoint, $params);
    }

    public function trackPurchase($email, $items, $campaignId=NULL, $dataFields=array()) {
        $endpoint = '/api/commerce/trackPurchase';
        $params = array(
            'user' => array(
                'email' => $email
            ),
            'items' => $items
        );
        if (!empty($dataFields)) {
            $params['user']['dataFields'] = $dataFields;
        }
        if ($campaignId != NULL) {
            $params['campaignId'] = $campaignId;
        }
        $this->callIterableApi(Iterable_TrackOrderPlaced_Model_TrackingEventTypes::EVENT_TYPE_ORDER, $endpoint, $params);
    }

}
