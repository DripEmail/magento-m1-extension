Feature: Admin Product Interactions

  I want Drip informed when I make product changes

  Scenario: When I create a new product
    Given I am logged into the admin interface
      And I have set up a multi-store configuration
      And I have configured Drip to be enabled for 'site1_website'
      And I have configured a simple widget for 'site1'
    Then A simple product event should be sent to Drip
