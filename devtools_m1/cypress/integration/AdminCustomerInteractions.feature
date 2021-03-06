Feature: Admin Customer Interactions

  I want to send customer events to Drip when an admin interacts with a customer.

  Scenario: An admin creates an unsubscribed account and then subscribes
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have configured Drip to be enabled for 'site1_store_view'
    When I create a 'site1_website' user in the admin
    Then A new 'unsubscribed' subscriber event should be sent to Drip
    When An admin subscribes to the general newsletter
    Then A 'subscribed' event should be sent to Drip

  Scenario: An admin creates an unsubscribed account and then subscribes when not configured for Drip
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have configured Drip to be enabled for 'Default Store View'
    When I create a 'site1_website' user in the admin
    Then No web requests are sent
    When An admin subscribes to the general newsletter
    Then No web requests are sent
