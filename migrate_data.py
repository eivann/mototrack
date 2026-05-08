import re

def convert_mysql_to_postgres(mysql_sql):
    # Remove backticks
    postgres_sql = mysql_sql.replace('`', '')
    
    # Handle tinyint(1) to boolean for specific columns if necessary
    # In the dump: low_stock_notified is 0 or 1. Postgres expects true/false.
    # We can use a regex for the values block.
    
    # Example line: (3, 'ENG-BAO-9027', 'Engine', ..., 0),
    # The last column is low_stock_notified.
    
    # Simple replacement for common cases
    # Replace ', 0),' with ', false),'
    # Replace ', 1),' with ', true),'
    # Replace ', 0);' with ', false);'
    # Replace ', 1);' with ', true);'
    
    postgres_sql = re.sub(r', 0\)([;,])', r', false)\1', postgres_sql)
    postgres_sql = re.sub(r', 1\)([;,])', r', true)\1', postgres_sql)
    
    return postgres_sql

def extract_table_data(file_path, table_name):
    with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()
        
    # Find all INSERT INTO table_name ... statements
    pattern = rf"INSERT INTO {re.escape('`'+table_name+'`')} .*? VALUES\s+(.*?);"
    matches = re.finditer(pattern, content, re.DOTALL)
    
    inserts = []
    for match in matches:
        values_part = match.group(1)
        # Convert MySQL syntax to Postgres
        clean_values = convert_mysql_to_postgres(values_part)
        # Construct the Postgres INSERT statement
        # We need the column list too.
        column_pattern = rf"INSERT INTO {re.escape('`'+table_name+'`')} \((.*?)\) VALUES"
        col_match = re.search(column_pattern, match.group(0))
        columns = col_match.group(1).replace('`', '')
        
        inserts.append(f"INSERT INTO {table_name} ({columns}) VALUES\n{clean_values};")
        
    return "\n\n".join(inserts)

# Tables to migrate
tables = ['inventory_items', 'users', 'repairs', 'pre_orders', 'sales']
dump_file = r'e:\laragon\www\mototrack\mototrack_dump.sql'
output_file = 'supabase_import.sql'

all_sql = []
# Disable FK checks during import
all_sql.append("SET session_replication_role = 'replica';")

for table in tables:
    print(f"Extracting {table}...")
    table_sql = extract_table_data(dump_file, table)
    if table_sql:
        all_sql.append(f"-- Data for {table}")
        all_sql.append(f"DELETE FROM {table};") # Clear existing demo data
        all_sql.append(table_sql)
    else:
        print(f"No data found for {table}")

# Re-enable FK checks
all_sql.append("SET session_replication_role = 'origin';")

with open(output_file, 'w', encoding='utf-8') as f:
    f.write("\n\n".join(all_sql))

print(f"Done! SQL saved to {output_file}")
