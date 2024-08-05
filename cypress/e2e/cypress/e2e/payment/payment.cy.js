/// <reference types="cypress" />

describe('Outgoing Payments Test', () => {
    beforeEach(() => {
        cy.visitUrl('/login');
    });

    it('can create Payments', () => {
        // Login as admin user
        cy.login('demo@primavip.co', 'demo2024');
        cy.wait(3000);
        cy.url().should('eq', `${ Cypress.env('url') }/admin`);

        // Go to Payments
        cy.get('.fi-sidebar-nav-groups')
            .contains('Payments')
            .should('be.visible')
            .click();

        // Go to New Payments
        cy.wait(10000);

        cy.get('a.fi-btn')
            .contains('New payment')
            .click();

        cy.wait(10000);

        // Select a type ( Venue ) the month should be dynamic based on the current month and currency = USD
        cy.get('#data\\.type')
            .select('Venue');

        const currentMonth = new Date().toLocaleString('default', {
            month: 'long',
        });
        cy.get('#data\\.month')
            .select(currentMonth);

        cy.get('#data\\.currency')
            .select('USD');

        cy.wait(5000);

        // Wait for the Total to update
        cy.get('#data\\.amount')
            .should('not.have.value', '0');

        // Click Create
        cy.get('button')
            .contains('Create')
            .click();

        cy.wait(10000);

        cy.get('.fi-no-notification-title')
            .should('contain.text', 'Created');
    });

    it('can export Payments', () => {
        // Login as admin user
        cy.login('demo@primavip.co', 'demo2024');
        cy.wait(3000);
        cy.url().should('eq', `${ Cypress.env('url') }/admin`);

        // Go to Payments
        cy.get('.fi-sidebar-nav-groups')
            .contains('Payments')
            .should('be.visible')
            .click();

        cy.wait(10000);

        cy.get('.fi-ta-actions-cell')
            .contains('View')
            .should('be.visible')
            .click();

        cy.wait(10000);

        // Export Payments
        cy.get('.fi-btn').contains('Export')
            .should('be.visible')
            .click();

        cy.wait(11000);

        cy.get('.fi-modal-footer-actions')
            .contains('Export')
            .click();

        cy.wait(10000);

        cy.get('.fi-no-notification-title')
            .should(
                'contain.text',
                'Export started',
            );
    });

    it('can view Payments as Venue user', () => {
        // Login as Venue user
        cy.login('venue@primavip.co', 'demo2024');
        cy.wait(3000);
        cy.url().should('eq', `${ Cypress.env('url') }/restaurant`);

        // Go to Payments
        cy.get('.fi-sidebar-nav-groups')
            .contains('My Payments')
            .should('be.visible')
            .click();

        cy.get('.fi-table-cell-payment\\.status')
            .should('be.visible');
    });
});
