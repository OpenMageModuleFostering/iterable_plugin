<?php

class Iterable_TrackOrderPlaced_Model_TrackingEventTypes
{

    const EVENT_TYPE_ORDER = 'order';
    const EVENT_TYPE_USER = 'user';
    const EVENT_TYPE_CART_UPDATED = 'cartUpdated';
    const EVENT_TYPE_NEWSLETTER_SUBSCRIBE = 'newsletterSubscribe';
    const EVENT_TYPE_NEWSLETTER_UNSUBSCRIBE = 'newsletterUnsubscribe';

    /** @const */
    private static $eventTypes = array(
        self::EVENT_TYPE_ORDER => 'Orders',
        self::EVENT_TYPE_USER => 'User',
        self::EVENT_TYPE_CART_UPDATED => 'Cart Updated',
        self::EVENT_TYPE_NEWSLETTER_SUBSCRIBE => 'Newsletter Subscribe',
        self::EVENT_TYPE_NEWSLETTER_UNSUBSCRIBE => 'Newsletter Unsubscribe'
    );

    public function toOptionArray()
    {
        $events = array();
        foreach (self::$eventTypes as $key => $name) {
            $events[] = array('value' => $key, 'label'=>Mage::helper('trackorderplaced')->__($name));
        }
        return $events;
    }

}