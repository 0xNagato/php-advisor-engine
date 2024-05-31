/// <reference types="cypress" />

describe('Concierge User Profile 2FA Tests', () => {
    beforeEach(() => {
        cy.visitUrl("/login");
    });

    it('can redirect to 2FA when modifying email or phone and fail with a random number', () => {
        // Login as concierge user
        cy.login("concierge@primavip.co", "demo2024");
        cy.wait(3000);
        cy.url().should("eq", `${Cypress.env("url")}/messages`);

        // Go to User Profile page
        cy.get(".fi-user-menu .fi-dropdown-trigger").click();
        cy.contains(".fi-dropdown-list-item-label", "Demo Concierge")
            .click()
            .url()
            .should("eq", `${Cypress.env("url")}/my-settings`);

        // Try to modify first name, last name
        cy.get('#data\\.first_name')
            .click()
            .clear()
            .type('Jhon');

        cy.get('#data\\.last_name')
            .click()
            .clear()
            .type('Doe');

        cy.get("button")
            .contains("Update Profile")
            .click();

        // Verify redirect to 2FA
        cy.wait(6000)
            .get('[data-cy="twoFactorModal"]')
            .should('be.visible');

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

        // 2FA should fail
        cy.get('[data-cy="twoFactorModal"]')
            .should('contain', 'The provided 2FA code is incorrect.');

        // Visit the profile page again
        cy.get(".fi-user-menu .fi-dropdown-trigger").click();
        cy.contains(".fi-dropdown-list-item-label", "Demo Concierge")
            .click()
            .url()
            .should("eq", `${Cypress.env("url")}/my-settings`);

        // Verify that first and last names have not changed
        cy.get('[data-cy="firstName"] input')
            .should('have.value', 'Demo');

        cy.get('[data-cy="lastName"] input')
            .should('have.value', 'Concierge');
    });
});
