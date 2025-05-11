#!/bin/bash

# Path to the CSV file
CSV_FILE="Venue Onboarding (May 2025) - Venue Onboarding (1).csv"

# Partner ID to use
PARTNER_ID=27

# Total number of venue groups (from running the command)
TOTAL_GROUPS=87

# Number of groups to process per chunk
CHUNK_SIZE=10

# Output CSV file for venue agreement links
OUTPUT_FILE="ibiza_venue_agreement_links.csv"

# Remove existing output file if it exists
if [ -f "$OUTPUT_FILE" ]; then
    rm "$OUTPUT_FILE"
    echo "Removed existing output file: $OUTPUT_FILE"
fi

# Process in chunks
for ((i=0; i<TOTAL_GROUPS; i+=CHUNK_SIZE)); do
    echo "Processing chunk $((i/CHUNK_SIZE + 1)) of $((TOTAL_GROUPS/CHUNK_SIZE + 1))"
    php artisan prima:import-ibiza-venues "$CSV_FILE" --partner-id=$PARTNER_ID --start=$i --count=$CHUNK_SIZE --output="$OUTPUT_FILE"
    
    # Add a small delay between chunks to give the system a break
    sleep 2
done

echo "Import completed!"
echo "Agreement links saved to $OUTPUT_FILE"