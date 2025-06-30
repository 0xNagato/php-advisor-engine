// =============================================================================================
// @ts-nocheck
//  Prima – Venue On-Boarding Google Apps Script
//  This single file, when added to a Google Sheet, creates a complete venue-onboarding workflow
//  including reference data, validations, custom menu & helper functions.
//  Author: ChatGPT 2025-05-07
// =============================================================================================

/**
 * Sheet & column constants
 */
const SHEET_MAIN = 'Venue Onboarding';
const SHEET_REFERENCE = 'Reference Data';
const SHEET_INSTRUCTIONS = 'Instructions';

const HEADER_ROW = 1;

// Column indices (1-based)
const COL_VENUE_NAME = 1; // A
const COL_REGION = 2; // B
const COL_CONTACT_FIRST_NAME = 3; // C
const COL_CONTACT_LAST_NAME = 4; // D
const COL_CONTACT_PHONE = 5; // E
const COL_CONTACT_EMAIL = 6; // F
const COL_NEIGHBORHOOD = 7; // G
const COL_SPECIALTIES = 8; // H – comma separated
const COL_CUISINES = 9; // I – comma separated
const COL_TIME_START_BASE = 10; // J – Monday start, then alternating start/end through Sunday

const DAYS_OF_WEEK = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

/**
 * Colour helper constants
 */
const COLOR_INVALID_BG = '#f8d7da'; // light red (bootstrap danger-100)
const COLOR_VALID_BG = null;      // reset

// -----------------------------------------------------------------------------
//  REFERENCE DATA  — mirrors the PHP seed data supplied in the repo
// -----------------------------------------------------------------------------

/**  List of regions (id + display name) */
const REGIONS = [
    { id: 'miami', name: 'Miami' },
    { id: 'ibiza', name: 'Ibiza' },
    { id: 'mykonos', name: 'Mykonos' },
    { id: 'paris', name: 'Paris' },
    { id: 'london', name: 'London' },
    { id: 'st_tropez', name: 'St. Tropez' },
    { id: 'new_york', name: 'New York' },
    { id: 'los_angeles', name: 'Los Angeles' },
    { id: 'las_vegas', name: 'Las Vegas' },
];

/** Neighborhoods per region */
const NEIGHBORHOODS_MAP = {
    'Miami': [
        'Brickell', 'South Beach', 'Wynwood', 'Coconut Grove', 'Coral Gables', 'Edgewater',
        'Downtown Miami', 'Little Havana', 'Design District', 'Key Biscayne', 'Miami Beach',
        'Aventura', 'Doral',
    ],
    'Ibiza': [
        'Ibiza Town (Eivissa)', 'Sant Antoni de Portmany', 'Santa Eulària des Riu',
        "Playa d'en Bossa", 'Talamanca', 'Portinatx', 'San José',
        'Figueretas', 'Es Canar', 'Cala Llonga', 'Es Cavallet', 'Cala Vadella', 'San Miguel',
        'Santa Gertrudis', 'Formentera',
    ],
    'Mykonos': [
        'Mykonos Town (Chora)', 'Little Venice', 'Matogianni Street', 'Ornos', 'Psarou Beach',
        'Platis Gialos', 'Ano Mera', 'Agios Ioannis', 'Tourlos', 'Kalafati', 'Paradise Beach',
        'Super Paradise Beach', 'Elia Beach',
    ],
    'Paris': [
        'Le Marais', 'Montmartre', 'Latin Quarter', 'Champs-Élysées', 'Saint-Germain-des-Prés',
        'Belleville', 'La Défense', 'Bastille', 'Canal Saint-Martin', 'Pigalle', 'Opéra', 'Louvre',
        'Eiffel Tower (7th arrondissement)',
    ],
    'London': [
        'Soho', 'Camden', 'Kensington', 'Chelsea', 'Notting Hill', 'Shoreditch', 'Mayfair', 'Brixton',
        'Greenwich', 'Canary Wharf', 'Covent Garden', 'Westminster', 'Knightsbridge',
    ],
    'St. Tropez': [
        'Vieux Port', 'La Ponche', 'Pampelonne Beach', 'Les Salins', 'Gassin', 'Ramatuelle', 'Port Grimaud',
    ],
    'New York': [
        'SoHo', 'Harlem', 'Upper East Side', 'Tribeca', 'Williamsburg', 'DUMBO', 'Park Slope', 'Astoria',
        'Long Island City', 'Riverdale', 'St. George', 'Chelsea', 'Greenwich Village', 'Financial District',
        'West Village', 'Midtown', 'Upper West Side', 'East Village',
    ],
    'Los Angeles': [
        'Hollywood', 'Beverly Hills', 'Downtown LA', 'Santa Monica', 'Venice Beach', 'Silver Lake',
        'Koreatown', 'Malibu', 'Echo Park', 'Westwood', 'West Hollywood', 'Bel Air', 'Los Feliz',
    ],
    'Las Vegas': [
        'The Strip', 'Downtown Las Vegas', 'Summerlin', 'Henderson', 'Spring Valley', 'Green Valley',
        'Anthem', 'Southern Highlands', 'Arts District', 'Chinatown',
    ],
};

/** Cuisine list */
const CUISINES = [
    'American', 'Asian', 'Chinese', 'French', 'Fusion', 'Gluten-Free', 'Greek', 'Grill', 'Indian', 'International',
    'Italian', 'Japanese', 'Korean', 'Mediterranean', 'Mexican', 'Middle Eastern', 'Peruvian',
    'Seafood', 'Spanish', 'Steakhouse', 'Thai', 'Turkish', 'Vegan'
];

/** Specialty list */
const SPECIALTIES = [
    'Family Friendly', 'Farm-to-Table', 'Fine Dining', 'Live Music/DJ', 'Michelin/Repsol Recognition',
    'On the Beach', 'Romantic Atmosphere', 'Rooftop', 'Scenic view', 'Sunset view',
    'Traditional Ibiza', 'Vegetarian/Vegan Options', 'Waterfront'
];

// =============================================================================================
//  ENTRY POINTS / TRIGGERS
// =============================================================================================

/** Adds custom menu when the spreadsheet is opened */
function onOpen() {
    SpreadsheetApp.getUi()
        .createMenu('Venue Onboarding')
        .addItem('Add Sample Row', 'addSampleRow')
        .addItem('Validate Entire Sheet', 'validateEntireSheet')
        .addSeparator()
        .addItem('Show Instructions', 'showInstructions')
        .addSeparator()
        .addItem('Export to CSV', 'exportToCsv')
        .addSeparator()
        .addItem('Pick Cuisines', 'openCuisineSelector')
        .addItem('Pick Specialties', 'openSpecialtySelector')
        .addToUi();
}

/** One-off setup to build sheets, reference data & validations */
function setupVenueOnboarding() {
    const ss = SpreadsheetApp.getActive();

    // --- Main sheet
    let main = ss.getSheetByName(SHEET_MAIN);
    if (!main) {
        main = ss.insertSheet(SHEET_MAIN, 0);
    }
    buildMainSheetHeader(main);

    // --- Reference data sheet (hidden)
    let ref = ss.getSheetByName(SHEET_REFERENCE);
    if (!ref) {
        ref = ss.insertSheet(SHEET_REFERENCE);
        ref.hideSheet();
    } else {
        ref.clearContents();
    }
    populateReferenceSheet(ref);

    // --- Instructions sheet
    let instructions = ss.getSheetByName(SHEET_INSTRUCTIONS);
    if (!instructions) {
        instructions = ss.insertSheet(SHEET_INSTRUCTIONS);
    }
    populateInstructionsSheet(instructions);
}

/** Reacts to user edits – does per-cell validation & dynamic neighbourhood dropdowns */
function onEdit(e) {
    const range = e.range;
    const sheet = range.getSheet();
    if (sheet.getName() !== SHEET_MAIN) return;

    const row = range.getRow();
    const col = range.getColumn();
    if (row === HEADER_ROW) return;

    // Region changed: rebuild neighbourhood dropdown for this row
    if (col === COL_REGION) {
        applyNeighbourhoodValidation(sheet, row);
    }

    // Validate individual cell depending on column
    validateCell(sheet, row, col);
}

// =============================================================================================
//  SHEET BUILDERS
// =============================================================================================

/** Writes header row and base validations */
function buildMainSheetHeader(sheet) {
    const headers = [
        'Venue Name *', 'Region *', 'Contact First Name *', 'Contact Last Name *', 'Contact Phone Number *',
        'Contact Email Address *', 'Neighborhood *', 'Specialties (comma-separated)', 'Cuisines (comma-separated)',
    ];
    DAYS_OF_WEEK.forEach(day => {
        headers.push(`${day} Start Time (HH:MM)`);
        headers.push(`${day} End Time (HH:MM)`);
    });

    sheet.getRange(HEADER_ROW, 1, 1, headers.length).setValues([headers]);
    sheet.setFrozenRows(1);
    sheet.setColumnWidths(1, headers.length, 160);

    // Highlight required headers
    headers.forEach((h, idx) => {
        if (h.includes('*')) {
            sheet.getRange(HEADER_ROW, idx + 1).setBackground('#d9ead3'); // light green
        }
    });

    // Region dropdown (column B)
    const regionRange = sheet.getRange(HEADER_ROW + 1, COL_REGION, sheet.getMaxRows());
    const regionRule = SpreadsheetApp.newDataValidation()
        .requireValueInList(REGIONS.map(r => r.name), true).setAllowInvalid(false).build();
    regionRange.setDataValidation(regionRule);
}

/** Populates the hidden reference sheet with data & creates named ranges */
function populateReferenceSheet(ref) {
    const regionNames = REGIONS.map(r => r.name);
    ref.getRange(1, 1).setValue('Regions');
    ref.getRange(2, 1, regionNames.length, 1).setValues(regionNames.map(n => [n]));

    // Cuisines
    ref.getRange(1, 2).setValue('Cuisines');
    ref.getRange(2, 2, CUISINES.length, 1).setValues(CUISINES.map(c => [c]));

    // Specialties
    ref.getRange(1, 3).setValue('Specialties');
    ref.getRange(2, 3, SPECIALTIES.length, 1).setValues(SPECIALTIES.map(s => [s]));

    // Region-specific neighbourhood columns, starting at column 5
    let col = 5;
    for (const region of regionNames) {
        ref.getRange(1, col).setValue(region);
        const list = NEIGHBORHOODS_MAP[region] || [];
        if (list.length > 0) {
            ref.getRange(2, col, list.length, 1).setValues(list.map(n => [n]));
        }
        // Create named range for neighbourhoods (spaces removed)
        const safeName = `Neighborhoods_${region.replace(/\s+/g, '_')}`;
        const lastRow = 1 + Math.max(list.length, 1);
        ref.getParent().setNamedRange(safeName, ref.getRange(2, col, lastRow - 1, 1));
        col++;
    }

    // Create global named ranges for cuisines & specialties
    ref.getParent().setNamedRange('Cuisines_List', ref.getRange(2, 2, CUISINES.length, 1));
    ref.getParent().setNamedRange('Specialties_List', ref.getRange(2, 3, SPECIALTIES.length, 1));
    ref.getParent().setNamedRange('Regions_List', ref.getRange(2, 1, regionNames.length, 1));
}

/** Writes basic usage instructions */
function populateInstructionsSheet(sh) {
    const lines = [
        ['Venue On-Boarding Sheet – Instructions'],
        ['1. Fill in all *required* fields.'],
        ['2. Select a region first, then pick a neighbourhood from the new dropdown.'],
        ['3. Specialties & Cuisines accept comma-separated lists; invalid values will be highlighted.'],
        ['4. Daily start/end times must be in 24-hour HH:MM format.'],
        ['5. Use the "Venue Onboarding" custom menu for helpers & CSV export.'],
    ];
    sh.clear();
    sh.getRange(1, 1, lines.length, 1).setValues(lines);
    sh.setColumnWidth(1, 600);
}

// =============================================================================================
//  VALIDATION HELPERS
// =============================================================================================

/** Applies neighbourhood dropdown to a given row, based on the region value */
function applyNeighbourhoodValidation(sheet, row) {
    const region = sheet.getRange(row, COL_REGION).getValue().toString().trim();
    const neighbourhoodCell = sheet.getRange(row, COL_NEIGHBORHOOD);

    if (!region) {
        neighbourhoodCell.clearDataValidations();
        return;
    }
    const list = NEIGHBORHOODS_MAP[region];
    if (!list || list.length === 0) {
        neighbourhoodCell.clearDataValidations();
        return;
    }
    const rule = SpreadsheetApp.newDataValidation()
        .requireValueInList(list, true)
        .setAllowInvalid(false)
        .build();
    neighbourhoodCell.setDataValidation(rule);
}

/** Validates a single cell and colours accordingly */
function validateCell(sheet, row, col) {
    const cell = sheet.getRange(row, col);
    const value = cell.getValue();

    let valid = true;
    let message = '';

    switch (col) {
        case COL_CONTACT_EMAIL:
            valid = isValidEmail(value);
            message = valid ? '' : 'Invalid email format';
            break;
        case COL_SPECIALTIES:
            valid = validateCommaList(value, SPECIALTIES);
            message = valid ? '' : 'Unknown specialty found';
            break;
        case COL_CUISINES:
            valid = validateCommaList(value, CUISINES);
            message = valid ? '' : 'Unknown cuisine found';
            break;
        default:
            // Time columns
            if (col >= COL_TIME_START_BASE) {
                valid = isValidTime(value);
                message = valid ? '' : 'Time format must be HH:MM (24-hour)';
            }
    }

    decorateValidation(cell, valid, message);
}

/** Decorates a cell based on validation result */
function decorateValidation(cell, isValid, msg) {
    cell.setBackground(isValid ? COLOR_VALID_BG : COLOR_INVALID_BG);
    if (msg) {
        cell.setNote(msg);
    } else {
        cell.clearNote();
    }
}

/** Checks if a list cell contains only allowed tokens */
function validateCommaList(val, allowed) {
    if (!val) return true; // empty allowed
    const parts = val.split(',').map(s => s.trim()).filter(Boolean);
    return parts.every(p => allowed.indexOf(p) !== -1);
}

function isValidEmail(val) {
    if (!val) return false;
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
}

function isValidTime(val) {
    if (!val) return false;
    const m = /^(\d{1,2}):(\d{2})$/.exec(val);
    if (!m) return false;
    const h = +m[1];
    const min = +m[2];
    return h >= 0 && h < 24 && min >= 0 && min < 60;
}

// =============================================================================================
//  MENU ACTIONS
// =============================================================================================

/** Inserts one sample row with example data */
function addSampleRow() {
    const ss = SpreadsheetApp.getActive();
    const sheet = ss.getSheetByName(SHEET_MAIN);
    if (!sheet) return;
    const nextRow = sheet.getLastRow() + 1;

    const sampleTimes = [];
    DAYS_OF_WEEK.forEach(() => {
        sampleTimes.push('11:00', '23:00');
    });

    const row = [
        'Sample Venue', 'Miami', 'John', 'Doe', '+1 305-555-1234', 'john.doe@example.com',
        'Brickell', 'Waterfront, Fine Dining', 'American, Italian',
        ...sampleTimes,
    ];
    sheet.getRange(nextRow, 1, 1, row.length).setValues([row]);
    // Re-apply neighbourhood validation for the new row
    applyNeighbourhoodValidation(sheet, nextRow);
}

/** Validates all populated rows on the sheet */
function validateEntireSheet() {
    const ss = SpreadsheetApp.getActive();
    const sheet = ss.getSheetByName(SHEET_MAIN);
    if (!sheet) return;
    const last = sheet.getLastRow();
    let errors = 0;
    for (let r = HEADER_ROW + 1; r <= last; r++) {
        const venueName = sheet.getRange(r, COL_VENUE_NAME).getValue();
        if (!venueName) continue; // skip empty rows

        // iterate columns we care about
        const maxCol = COL_TIME_START_BASE + DAYS_OF_WEEK.length * 2 - 1;
        for (let c = COL_CONTACT_EMAIL; c <= maxCol; c++) {
            validateCell(sheet, r, c);
            if (sheet.getRange(r, c).getBackground() === COLOR_INVALID_BG) errors++;
        }
    }
    SpreadsheetApp.getUi().alert(errors ? `${errors} validation errors found.` : 'All good – no problems detected.');
}

/** Switches to the instructions sheet */
function showInstructions() {
    const ss = SpreadsheetApp.getActive();
    const sh = ss.getSheetByName(SHEET_INSTRUCTIONS);
    if (sh) ss.setActiveSheet(sh);
}

/** Exports onboarding sheet data to Drive as CSV */
function exportToCsv() {
    const ss = SpreadsheetApp.getActive();
    const sheet = ss.getSheetByName(SHEET_MAIN);
    if (!sheet) return;

    const data = sheet.getDataRange().getValues();
    const rows = data.filter((row, idx) => idx === 0 || row[0]); // keep header + non-blank

    // Convert to CSV (escape quotes)
    const csv = rows.map(r => r.map(v => {
        const str = v === null ? '' : v.toString();
        if (str.includes(',') || str.includes('"')) {
            return '"' + str.replace(/"/g, '""') + '"';
        }
        return str;
    }).join(',')).join('\n');

    const tz = ss.getSpreadsheetTimeZone();
    const stamp = Utilities.formatDate(new Date(), tz, 'yyyyMMdd_HHmmss');
    const fileName = `venue_onboarding_${stamp}.csv`;
    const file = DriveApp.createFile(fileName, csv, MimeType.CSV);

    SpreadsheetApp.getUi().alert(`CSV created in your Drive: ${file.getName()}`);
}

// =============================================================================================
//  SIDEBAR PICKERS
// =============================================================================================

/** Opens sidebar for cuisine multi-select */
function openCuisineSelector() {
    openTagSidebar('Select Cuisines', CUISINES);
}

/** Opens sidebar for specialty multi-select */
function openSpecialtySelector() {
    openTagSidebar('Select Specialties', SPECIALTIES);
}

/** Core helper to open a sidebar with checkbox list for tags */
function openTagSidebar(title, tags) {
    const htmlContent = HtmlService.createHtmlOutput(buildSidebarHtml(title, tags))
        .setTitle(title)
        .setWidth(300);
    SpreadsheetApp.getUi().showSidebar(htmlContent);
}

/** Builds HTML for sidebar UI */
function buildSidebarHtml(title, tags) {
    const tagCheckboxes = tags.map(t => {
        return `<label style="display:block;padding:4px 0;"><input type=\"checkbox\" value=\"${t.replace(/"/g, '&quot;')}\"> ${t}</label>`;
    }).join('');

    return `<!DOCTYPE html>
<html>
<head>
  <base target="_top">
  <style>
    body { font-family: Arial, sans-serif; margin:10px; }
    button { margin-top:10px; padding:6px 12px; }
  </style>
</head>
<body>
  <h3>${title}</h3>
  <div id="checkboxes">
    ${tagCheckboxes}
  </div>
  <button onclick="applySelection()">Insert</button>
  <script>
    // Pre-check any already present in the active cell
    (function prefill() {
      google.script.run.withSuccessHandler(function(val){
        if(!val) return;
        const current = val.split(',').map(function(s){return s.trim();}).filter(Boolean);
        document.querySelectorAll('#checkboxes input').forEach(function(cb){
          if(current.indexOf(cb.value) !== -1){ cb.checked = true; }
        });
      }).getActiveCellValue();
    })();

    function applySelection(){
      const selected = Array.from(document.querySelectorAll('#checkboxes input:checked'))
        .map(function(cb){ return cb.value; });
      google.script.run.withSuccessHandler(function(){
        google.script.host.close();
      }).setActiveCellValue(selected.join(', '));
    }
  </script>
</body>
</html>`;
}

/** Returns active cell value to the client */
function getActiveCellValue() {
    const cell = SpreadsheetApp.getActiveSheet().getActiveCell();
    return cell.getValue();
}

/** Sets active cell value from sidebar */
function setActiveCellValue(val) {
    const cell = SpreadsheetApp.getActiveSheet().getActiveCell();
    cell.setValue(val);
    // Trigger validation styling for cell
    const col = cell.getColumn();
    const row = cell.getRow();
    validateCell(cell.getSheet(), row, col);
}

// =============================================================================================
//  END OF FILE
// =============================================================================================
