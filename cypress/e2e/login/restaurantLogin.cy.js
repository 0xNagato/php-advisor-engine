/// <reference types="cypress" />

describe("Venue Login Test", () => {
    beforeEach(() => {
        cy.visitUrl("/login");
    });

    it("can login with correct credentials", () => {
        cy.login("venue@primavip.co", "demo2024");

        cy.wait(3000);
        cy.url().should("eq", `${ Cypress.env("url") }/restaurant`);
    });

    it("can display error messages for incorrect credentials", () => {
        cy.login("venue@primavip.co", "test");

        cy.wait(3000);
        cy.get(".fi-fo-field-wrp-error-message").contains(
            "These credentials do not match our records.",
        );
    });

    it("can show validation message when password field is empty", () => {
        cy.login("venue@primavip.co");

        cy.wait(3000);
        cy.get('input[type="password"]').then(($input) => {
            expect($input[0].validationMessage).to.eq(
                "Please fill in this field.",
            );
        });
    });
});
