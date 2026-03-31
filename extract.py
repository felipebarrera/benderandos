import os
log_file = os.path.expanduser('~/.gemini/antigravity/brain/1c92267e-78ff-4afe-b8ee-37b2147757e8/.system_generated/logs/overview.txt')
output_file = 'resources/views/public/agenda.blade.php'

with open(log_file, 'r', encoding='utf-8') as f:
    content = f.read()

start_marker = "@php\n    $tenantNombre = tenant('nombre')"
end_marker = "</html>"

start_idx = content.rfind(start_marker)
if start_idx != -1:
    end_idx = content.find(end_marker, start_idx)
    if end_idx != -1:
        extracted = content[start_idx:end_idx + len(end_marker)]
        with open(output_file, 'w', encoding='utf-8') as out:
            out.write(extracted)
        print("Success.")
    else:
        print("End marker not found")
else:
    print("Start marker not found")
