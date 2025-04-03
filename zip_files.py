import os
import shutil
import zipfile
import datetime

#This file needs to be edited to have rabbitMQ Connection, This verision was test to see if it works so it saves the zip on the local machine in /home/mike/ZipCell. The comment.txt also will be changed to better work with mysql.

def create_zip(folder_path, zip_filename):
    try:
        with zipfile.ZipFile(zip_filename, 'w', zipfile.ZIP_DEFLATED) as zipf:
            for root, _, files in os.walk(folder_path):
                for file in files:
                    file_path = os.path.join(root, file)
                    zipf.write(file_path, os.path.relpath(file_path, folder_path))
        print(f"Successfully created zip archive: {zip_filename}")
        return True
    except Exception as e:
        print(f"Error creating zip archive: {e}")
        return False

def get_files_to_zip():
    files_to_zip = []
    while True:
        file_path = input("Enter file path (or 'done' to finish): ")
        if file_path.lower() == 'done':
            break
        if not os.path.exists(file_path):
            print(f"Error: File '{file_path}' does not exist.")
            continue
        files_to_zip.append(file_path)
    return files_to_zip

def generate_version():
    now = datetime.datetime.now()
    return now.strftime("%Y_%m_%d_%H_%M_%S")

def main():
    files = get_files_to_zip()

    if not files:
        print("No files selected. Exiting.")
        return

    temp_folder = "temp_deploy_files"
    os.makedirs(temp_folder, exist_ok=True)

    for file in files:
        shutil.copy(file, temp_folder)

    version = generate_version()
    zip_filename = f"deploy_v{version}.zip"

    if create_zip(temp_folder, zip_filename):
        print(f"Files zipped successfully to {zip_filename}")
    else:
        print("Zipping failed.")
        shutil.rmtree(temp_folder)
        return

    comment = input("Enter a comment for this deployment: ")

    destination_path = "/home/mike/ZipCell"
    try:
        shutil.move(zip_filename, destination_path)
        print(f"Zip file sent to {destination_path}")
    except Exception as e:
        print(f"Error sending zip file: {e}")

    comment_filename = f"comment_v{version}.txt"
    try:
        with open(comment_filename, "w") as f:
            f.write(comment)
        shutil.move(comment_filename, destination_path)
        print(f"Comment file sent to {destination_path}")
    except Exception as e:
        print(f"Error sending comment file: {e}")

    shutil.rmtree(temp_folder)

if __name__ == "__main__":
    main()


