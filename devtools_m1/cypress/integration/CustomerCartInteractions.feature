Feature: Customer Cart Interactions

  I want to send cart and order events to Drip when a customer interacts with their cart.

  Scenario: A customer adds a simple product to their cart
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'Main Website'
      And I have configured a simple widget for 'main'
    When I open the 'main' homepage
      And I create an account
      And I add a 'simple' widget to my cart
    Then A simple cart event should be sent to Drip
    When I check out
    Then A simple order event should be sent to Drip

  Scenario: A customer adds a configurable product to their cart and sees data about the sub-item
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'Main Website'
      And I have configured a configurable widget
    When I open the 'main' homepage
      And I create an account
      And I add a 'configurable' widget to my cart
    Then A configurable cart event should be sent to Drip
    When I check out
    Then A configurable order event should be sent to Drip

    Scenario: A customer adds a configurable product where the sub-item is not individually visible
      Given I am logged into the admin interface
        And I have configured Drip to be enabled for 'Main Website'
        And I have configured a configurable widget with an invisible child
      When I open the 'main' homepage
        And I create an account
        And I add a 'configurable' widget to my cart
      Then A configurable cart event with parent image and url should be sent to Drip
      When I check out
      Then A configurable order event should be sent to Drip

  Scenario: A customer adds several configurable products to their cart and sees data about the sub-items
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'Main Website'
      And I have configured a configurable widget
    When I open the 'main' homepage
      And I create an account
      And I add a 'configurable' widget to my cart
      And I add a different 'configurable' widget to my cart
    Then Configurable cart events should be sent to Drip

  Scenario: A customer adds a grouped product to their cart and sees all the individual items
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'Main Website'
      And I have configured a grouped widget
    When I open the 'main' homepage
      And I create an account
      And I add a 'grouped' widget to my cart
    Then A grouped cart event should be sent to Drip
    When I check out
    Then A grouped order event should be sent to Drip

  # Note that we skip a test for virtual and downloadable products since they
  # are essentially the same as simple products, as far as we are concerned.

  Scenario: A customer adds a bundle product to their cart and sees the parent item
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'Main Website'
      And I have configured a bundle widget
    When I open the 'main' homepage
      And I create an account
      And I add a 'bundle' widget to my cart
    Then A bundle cart event should be sent to Drip
    When I check out
    Then A bundle order event should be sent to Drip

  Scenario: A customer adds a simple product to their cart when only that store is configured for Drip
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have configured Drip to be enabled for 'site1_website'
      And I have configured a simple widget for 'site1'
    When I open the 'site1' homepage
      And I create an account
      And I add a 'simple' widget to my cart
    Then A simple cart event should be sent to Drip
    When I check out
    Then A simple order event should be sent to Drip

  Scenario: A customer adds a simple product to their cart when a different store is configured for Drip
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have configured Drip to be enabled for 'site1_website'
      And I have configured a simple widget for 'main'
    When I open the 'main' homepage
      And I create an account
      And I add a 'simple' widget to my cart
    Then No web requests are sent
    When I check out
    Then No web requests are sent

  Scenario: A customer adds a simple product to their cart, and checks out as a guest.
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'Main Website'
      And I have configured a simple widget for 'main'
    When I open the 'main' homepage
      And I logout
      And I add a 'simple' widget to my cart
    When I begin check out as a guest
    Then A simple cart event should be sent to Drip
    When I complete check out as a guest
    Then A simple order event should be sent to Drip

  Scenario: A customer adds a simple product to their cart and drip receives a working abandoned cart url
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have configured Drip to be enabled for 'site1_website'
      And I have configured a simple widget for 'site1'
    When I open the 'site1' homepage
      And I create an account
      And I add a 'simple' widget to my cart
    Then A simple cart event should be sent to Drip
    When I open the abandoned cart url
    Then No web requests are sent
