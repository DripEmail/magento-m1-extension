Feature: Order Batch Sync

  I want to send all orders to Drip.

  Scenario: An admin syncs an order
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have configured Drip to be enabled for 'site1_store_view'
      And a customer exists
      And I have configured a configurable widget for website 'site1'
    When I create an order
      And I click order sync
    Then an order event is sent to Drip
