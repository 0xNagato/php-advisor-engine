/// <reference types="cypress" />

describe('Concierge Payment Profile Two-Factor Authentication (2FA) Tests', () => {
    beforeEach(() => {
        cy.visitUrl("/login");
    });
  
    it('should fail 2FA when attempting to update payment address with an incorrect code', () => {

        // Login as concierge user
        cy.login("concierge@primavip.co", "demo2024");
        cy.wait(3000);
        cy.url().should("eq", `${Cypress.env("url")}/messages`);

        // Navigate to Payment Information
        cy.get('.fi-sidebar-nav-groups')
            .contains('Payment Information')
            .should('be.visible')
            .click();

        // Update address fields
        cy.get('input[type="text"]#address_1')
            .should('have.class', 'fi-input')
            .click()
            .type('325 North St. Paul Street Suite 3100');
        
        cy.get('input[type="text"]#address_2')
            .should('have.class', 'fi-input')
            .click()
            .type('001 North St. Paul Street Suite 0');
        
        cy.get('input[type="text"]#city')
            .should('have.class', 'fi-input')
            .click()
            .type('Dallas');
        
        cy.get('input[type="text"]#state')
            .should('have.class', 'fi-input')
            .click()
            .type('TX');
        
        cy.get('input[type="text"]#zip')
            .should('have.class', 'fi-input')
            .click()
            .type('75201');
        
        cy.get('input[type="text"]#country')
            .should('have.class', 'fi-input')
            .click()
            .type('United States');

        // Submit the update
        cy.get("button")
            .contains("Update Address")
            .click();
        
        // Verify redirect to 2FA
        cy.wait(6000)
            .get('[data-cy="twoFactorModal"]')
            .should('be.visible');

        // Enter incorrect 2FA code
        const code = '123456';
        code.split('').forEach((digit, index) => {
            cy.get(`[data-cy="twoFactorModal"] input[type="number"]`)
                .eq(index)
                .type(digit);
        });
        
        cy.get("button")
            .contains("Submit")
            .click({ force: true });
        
        cy.wait(5000);
        
        cy.get("button")
            .contains("Submit")
            .click({ force: true });

        // Verify 2FA failure
        cy.get('[data-cy="twoFactorModal"]')
            .should('contain', 'The provided 2FA code is incorrect.');
    });
});
