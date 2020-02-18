Feature: Order Batch Sync

  I want to send all orders to Drip.

  Scenario: An admin syncs an order
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have configured Drip to be enabled for 'site1_store_view'
      And a customer exists for website id '100'
      And I have configured a configurable widget for website 'site1'
    When I create an order
      And I click order sync
    Then an order event is sent to Drip

  Scenario: An admin syncs an order with a virtual product
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have configured Drip to be enabled for 'site1_store_view'
      And a customer exists for website id '100'
      And I have configured a virtual widget for website 'site1'
    When I create an order for a virtual product
      And I click order sync
    Then an order event with virtual product is sent to Drip

  Scenario: An admin syncs an order with both a standard and a virtual product
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have configured Drip to be enabled for 'site1_store_view'
      And a customer exists for website id '100'
      And I have configured a virtual widget for website 'site1'
      And I have configured a configurable widget for website 'site1'
    When I create an order for both products
      And I click order sync
    Then an order event with both products is sent to Drip
