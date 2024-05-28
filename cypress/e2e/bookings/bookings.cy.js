/// <reference types="cypress" />

describe("Bookings Test", () => {
    beforeEach(() => {
        cy.visitUrl("/login");
        cy.login("demo@primavip.co", "demo2024");
        cy.wait(3000);
    });

    it("can filter bookings", () => {
        cy.visitUrl("/bookings");
        cy.get(".fi-dropdown-trigger > .fi-icon-btn").click();
        cy.get(".fi-fo-field-wrp-label").click();
        cy.get(".flex-wrap > .fi-badge").should("have.text", "Unconfirmed");
    });

    it("can view single booking page", () => {
        cy.visitUrl("/bookings");

        cy.get('[data-cy="booking-card"]')
            .first()
            .should("contain", "Booking")
            .click();
    });
});
