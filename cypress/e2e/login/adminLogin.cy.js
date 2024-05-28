/// <reference types="cypress" />

describe("Admin Login Test", () => {
    beforeEach(() => {
        cy.visitUrl("/login");
    });

    it("can log in with correct credentials", () => {
        cy.login("demo@primavip.co", "demo2024");

        cy.wait(3000);
        cy.url().should("eq", `${Cypress.env("url")}/admin`);
    });

    it("can display error messages for incorrect credentials", () => {
        cy.login("demo@primavip.co", "test");

        cy.wait(3000);
        cy.get(".fi-fo-field-wrp-error-message").contains(
            "These credentials do not match our records.",
        );
    });

    it("show validation errors when password field is empty", () => {
        cy.login("demo@primavip.co");

        cy.wait(3000);
        cy.get('input[type="password"]').then(($input) => {
            expect($input[0].validationMessage).to.eq(
                "Please fill in this field.",
            );
        });
    });
});
