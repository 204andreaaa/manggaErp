import re

files = [
    'resources/views/erp/purchase_orders/show.blade.php',
    'resources/views/erp/request_form/show.blade.php'
]

# We want to find:
# <div class="card-header border-bottom py-2 d-flex justify-content-between align-items-center cursor-pointer collapse-header" data-bs-toggle="collapse" data-bs-target="#collapsePoDetail">
#   <h6 class="mb-0 fw-bold"><i class="bx bx-chevron-down me-2"></i>PO Detail</h6>
#   <div class="d-flex gap-1 align-items-center" onclick="event.stopPropagation();">

pattern = re.compile(
    r'(<div class="card-header border-bottom py-\d d-flex justify-content-between align-items-center)\s+cursor-pointer collapse-header"\s+data-bs-toggle="collapse"\s+data-bs-target="([^"]+)">\s*(<h6.*?</h6>)\s*<div class="d-flex gap-1 align-items-center"(?: onclick="event\.stopPropagation\(\);")?>',
    re.DOTALL
)

for file in files:
    with open(file, 'r', encoding='utf-8') as f:
        content = f.read()
    
    def replacer(match):
        base_classes = match.group(1)
        target = match.group(2)
        h6 = match.group(3)
        return f'{base_classes}">\n      <div class="flex-grow-1 cursor-pointer collapse-header" data-bs-toggle="collapse" data-bs-target="{target}">\n        {h6}\n      </div>\n      <div class="d-flex gap-1 align-items-center">'

    new_content = pattern.sub(replacer, content)
    
    with open(file, 'w', encoding='utf-8') as f:
        f.write(new_content)

print('Replaced buttons headers successfully.')
