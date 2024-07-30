#!/bin/bash

set -e

npx cypress run --headless --browser chrome --config '{"specPattern":["cypress/tests/data/10-ApplicationSetup/{10-Installation,20-CreateContext}.cy.js","plugins/generic/customLocale/cypress/tests/functional/*.cy.{js,jsx,ts,tsx}"]}'