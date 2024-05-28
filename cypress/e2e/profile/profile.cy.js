/// <reference types="cypress" />

describe("User Profile Update Test", () => {
    beforeEach(() => {
        cy.visitUrl("/login");
        cy.login("demo@primavip.co", "demo2024");
    });

    it("can update users first name", () => {
        cy.get(".fi-user-menu .fi-dropdown-trigger").click();
        cy.contains(".fi-dropdown-list-item-label", "Demo Admin")
            .click()
            .url()
            .should("eq", `${Cypress.env("url")}/my-settings`);
        cy.get("first_name").click().clear().type("Jhon");
        cy.get("button").contains("Update Profile").click();
        cy.get("first_name").should("have.text", "Demo");
    });

    it("can update users password", () => {
        cy.get(".fi-user-menu .fi-dropdown-trigger").click();
        cy.contains(".fi-dropdown-list-item-label", "Change Password")
            .click()
            .url()
            .should("eq", `${Cypress.env("url")}/change-password`);
        cy.get("change-password").click().type("Old Password");
        cy.get("new-password").click().type("New Password");
        cy.get("new_password_confirmation-password")
            .click()
            .type("New Password");
        cy.get("button").contains("Update Password").click();

        cy.get(".fi-user-menu .fi-dropdown-trigger").click();
        cy.contains(".fi-dropdown-list-item-label", "Sign Out")
            .click()
            .should("eq", `${Cypress.env("url")}/login`);

        cy.login("demo@primavip.co", "New Password");
        cy.url().should("eq", `${Cypress.env("url")}/admin`);
    });

    it("can sign out", () => {
        cy.get(".fi-user-menu .fi-dropdown-trigger").click();
        cy.contains(".fi-dropdown-list-item-label", "Sign Out")
            .click()
            .should("eq", `${Cypress.env("url")}/login`);
    });
});
