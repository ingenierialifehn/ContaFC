import os

fav = '<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2300b4ff\'><path d=\'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-2h2v2zm0-4H7v-2h2v2zm0-4H7V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2zm4 8h-2v-2h2v2zm0-4h-2v-2h2v2zm0-4h-2V7h2v2z\'/></svg>">'

for root, dirs, files in os.walk('.'):
    for file in files:
        if file.endswith('.php'):
            path = os.path.join(root, file)
            with open(path, 'r', encoding='utf-8', errors='ignore') as f:
                content = f.read()
            
            if '<meta charset="UTF-8">' in content and fav not in content:
                new_content = content.replace('<meta charset="UTF-8">', '<meta charset="UTF-8">\n    ' + fav)
                with open(path, 'w', encoding='utf-8') as f:
                    f.write(new_content)
                print(f"Favicon added to {path}")
