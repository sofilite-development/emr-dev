-- Step 1: Add guardianid column to patient_data if not exists
SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'patient_data'
      AND column_name = 'guardianid'
);

SET @sql1 = IF(@col_exists = 0,
    'ALTER TABLE patient_data ADD COLUMN guardianid TEXT;',
    'SELECT "Column guardianid already exists."'
);
PREPARE stmt1 FROM @sql1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

-- Step 2: Insert into layout_options if guardianid field is not already present
INSERT INTO layout_options (
    form_id, field_id, group_id, title, seq, data_type, uor, fld_length, max_length,
    list_id, titlecols, datacols, default_value, edit_options, description,
    fld_rows, list_backup_id, source, conditions, validation, codes
)
SELECT 'DEM', 'guardianid', '1', 'Guardian', 250, 51, 1, 0, 0, '', 1, 1, '', '', 'Guardian',
       0, '', 'F', '', '', ''
WHERE NOT EXISTS (
    SELECT 1 FROM layout_options WHERE field_id = 'guardianid' AND form_id = 'DEM'
);

-- Step 1: Add guardian_relationship column if not exists
SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'patient_data'
      AND column_name = 'guardian_relationship'
);

SET @sql1 = IF(@col_exists = 0,
    'ALTER TABLE patient_data ADD COLUMN guardian_relationship TEXT DEFAULT NULL;',
    'SELECT "Column guardian_relationship already exists."'
);
PREPARE stmt1 FROM @sql1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

-- Step 2: Insert into layout_options as a dropdown linked to next_of_kin_relationship
INSERT INTO layout_options (
    form_id, field_id, group_id, title, seq, data_type, uor, fld_length, max_length,
    list_id, titlecols, datacols, default_value, edit_options, description,
    fld_rows, list_backup_id, source, conditions, validation, codes
)
SELECT 'DEM', 'guardian_relationship', '1', 'Guardian Relationship', 260, 1, 1, 0, 0,
       'next_of_kin_relationship', 1, 1, '', '["DAP"]', 'Guardian Relation',
       0, '', 'F', '', '', ''
WHERE NOT EXISTS (
    SELECT 1 FROM layout_options WHERE field_id = 'guardian_relationship' AND form_id = 'DEM'
);
