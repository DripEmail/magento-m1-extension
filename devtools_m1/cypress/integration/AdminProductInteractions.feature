Feature: Admin Product Interactions

  I want Drip informed when I make product changes

  Scenario: Simple product information C_UD in multi-store configs
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have configured Drip to be enabled for 'site1_website'
    When I have configured a simple widget for 'site1' in the admin site
    Then A simple product 'created' event should be sent to Drip for 'site1'
    When I have changed the price for the simple widget in the admin site
    Then A simple product 'updated' event should be sent to Drip for 'site1'
    When I delete the 'simple' widget in the admin site
    Then A simple product 'deleted' event should be sent to Drip for 'site1'

  Scenario: Simple product information C_UD in single-store configs
    Given I am logged into the admin interface
      And I have configured Drip to be enabled for 'Main Website'
    When I have configured a simple widget for 'main' in the admin site
    Then A simple product 'created' event should be sent to Drip for 'main'
    When I have changed the price for the simple widget in the admin site
    Then A simple product 'updated' event should be sent to Drip for 'main'
    When I delete the 'simple' widget in the admin site
    Then A simple product 'deleted' event should be sent to Drip for 'main'

  # Scenario: Configurable product information C_UD in single-store configs
  #   Given I am logged into the admin interface
  #     And I have configured Drip to be enabled for 'Main Website'
  #     And I have configured a configurable widget
  #   Then A simple product created event should be sent to Drip for 'main'
  #   When I have changed the price for the configurable widget in the admin site
  #   Then A simple product updated event should be sent to Drip for 'main'
