Feature: Customer Cart Interactions

  I want to send cart events to Drip when a customer interacts with their cart.

  Scenario: A customer adds a simple product to their cart
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'main'
      And I have configured a simple widget
    When I open the 'main' homepage
      And I create an account
      And I add a 'simple' widget to my cart
    Then A simple cart event should be sent to Drip

  Scenario: A customer adds a configured product to their cart and sees data about the sub-item
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'main'
      And I have configured a configurable widget
    When I open the 'main' homepage
      And I create an account
      And I add a 'configured' widget to my cart
    Then A configured cart event should be sent to Drip

  @focus
  Scenario: A customer adds a grouped product to their cart and sees all the individual items
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'main'
      And I have configured a grouped widget
    When I open the 'main' homepage
      And I create an account
      And I add a 'grouped' widget to my cart
    Then A grouped cart event should be sent to Drip

  Scenario: A customer adds a virtual product to their cart
  Scenario: A customer adds a bundle product to their cart
  Scenario: A customer adds a downloadable product to their cart
