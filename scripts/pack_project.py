import os
import shutil
import zipfile

SOURCE_DIR = r"D:\BCN\Project"
TARGET_DIR = r"D:\BCN\M-Retail POS Project (JIFFY POS)"
ZIP_TARGET_IN_PROJECT = r"D:\BCN\Project\M-Retail POS Project (JIFFY POS).zip"
ZIP_TARGET_IN_BCN = r"D:\BCN\M-Retail POS Project (JIFFY POS).zip"

FILES_AND_DIRS = [
    "index.html",
    "project_timeline.html",
    "admin",
    "api",
    "data",
    "scripts",
    "supabase_schema.sql",
    "CODEX_WORK_PACKAGE.md",
    "VERCEL_SUPABASE_DEPLOY_GUIDE.md",
    "README.md",
    "package.json",
    "package-lock.json",
    "vercel.json",
    ".env.example",
    "local_server.js",
    "verify_supabase.js",
    "test_api.js",
    "deploy.bat",
    "Project Detail Work Plan RM-Retail POS V2.0_20261015.xlsx",
    ".gitignore"
]

def main():
    print(f"Creating package directory: {TARGET_DIR}")
    if os.path.exists(TARGET_DIR):
        shutil.rmtree(TARGET_DIR)
    os.makedirs(TARGET_DIR, exist_ok=True)

    copied_items = []

    for item in FILES_AND_DIRS:
        src_path = os.path.join(SOURCE_DIR, item)
        dst_path = os.path.join(TARGET_DIR, item)
        if not os.path.exists(src_path):
            print(f"Warning: {item} not found in {SOURCE_DIR}")
            continue

        if os.path.isdir(src_path):
            # Clean copy excluding cache / temp
            def ignore_func(d, files):
                return [f for f in files if f.endswith('.pyc') or f == '__pycache__' or f.endswith('.tmp')]
            shutil.copytree(src_path, dst_path, ignore=ignore_func)
            print(f"Copied folder: {item}/")
        else:
            shutil.copy2(src_path, dst_path)
            print(f"Copied file: {item}")
        copied_items.append(item)

    print("\nCreating ZIP Archive...")
    for zip_path in [ZIP_TARGET_IN_PROJECT, ZIP_TARGET_IN_BCN]:
        with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as z:
            for root, dirs, files in os.walk(TARGET_DIR):
                for f in files:
                    full_p = os.path.join(root, f)
                    rel_p = os.path.relpath(full_p, TARGET_DIR)
                    z.write(full_p, arcname=os.path.join("M-Retail POS Project (JIFFY POS)", rel_p))
        print(f"Created ZIP: {zip_path} ({os.path.getsize(zip_path):,} bytes)")

    print(f"\nSuccessfully packed {len(copied_items)} items to {TARGET_DIR}")

if __name__ == '__main__':
    main()
