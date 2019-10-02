Feature: Customer Cart Interactions

  I want to send cart events to Drip when a customer interacts with their cart.

  Scenario: When a customer adds a simple product to their cart
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for main
      And I have configured a simple widget
    When I open the main homepage
      And I create an account
      And I add a simple widget to my cart
    Then A simple cart event should be sent to Drip

  Scenario: When a customer adds a configured product to their cart
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for main
      And I have configured a configurable widget
    When I open the main homepage
      And I create an account
      And I add a configured widget to my cart
    Then A configured cart event should be sent to Drip

# Grouped Product
# Configurable Product
# Virtual Product
# Bundle Product
# Downloadable Product
