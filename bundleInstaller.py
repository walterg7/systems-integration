import pika
import json
import configparser
import os
import subprocess
import zipfile
import shutil
import time
import logging

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

def process_bundle_message(ch, method, properties, body):
    try:
        message = json.loads(body)
        action = message.get('action')

        if action in ('new_bundle', 'rollback_bundle'):
            zip_filename = message.get('bundle_name')
            logging.info(f"Received bundle action '{action}' for: {zip_filename}")

            with open("last_bundle.txt", "w") as f:
                f.write(zip_filename)

            install_bundle(zip_filename)
        else:
            logging.info(f"Received non-bundle message: {message}")

    except json.JSONDecodeError:
        logging.error("Error decoding JSON message.")
    except Exception as e:
        logging.error(f"An error occurred in process_bundle_message: {e}")
    finally:
        ch.basic_ack(delivery_tag=method.delivery_tag)

def install_bundle(zip_filename):
    config = configparser.ConfigParser()
    config.read('RabbitMQ/RabbitMQ.ini')

    ssh_host = "10.147.19.36"
    ssh_user = "bundler"
    remote_path = "/home/bundler/Bundles"
    local_temp_path = "/var/www/rabbitmqphp_example/ZipCell"
    os.makedirs(local_temp_path, exist_ok=True)
    zip_file_path = ""

    try:
        logging.info(f"Attempting to SCP bundle: {zip_filename} from {ssh_user}@{ssh_host}:{remote_path} to {local_temp_path}/")

        scp_command = (
            f"sshpass -p '!Bundle123' scp -o StrictHostKeyChecking=no "
            f"{ssh_user}@{ssh_host}:{remote_path}/{zip_filename} {local_temp_path}/"
        )
        subprocess.run(scp_command, shell=True, check=True)

        zip_file_path = os.path.join(local_temp_path, zip_filename)
        logging.info(f"Successfully SCP'd bundle to: {zip_file_path}")

        with zipfile.ZipFile(zip_file_path, 'r') as zip_ref:
            logging.info(f"Extracting bundle: {zip_filename} to {local_temp_path}")
            zip_ref.extractall(local_temp_path)


        for extracted_root, _, extracted_files in os.walk(local_temp_path):
            for extracted_filename in extracted_files:
                extracted_file_path = os.path.join(extracted_root, extracted_filename)
                if extracted_filename != zip_filename:
                    for web_root, _, web_files in os.walk("/var/www"):
                        if extracted_filename in web_files:
                            original_file_path = os.path.join(web_root, extracted_filename)
                            try:
                                shutil.copy2(extracted_file_path, original_file_path)
                                logging.info(f"Replaced: {original_file_path}")
                            except Exception as replace_err:
                                logging.error(f"Error replacing {original_file_path}: {replace_err}")

        logging.info(f"Bundle {zip_filename} installed successfully.")

    except subprocess.CalledProcessError as e:
        logging.error(f"Error during SSH or SCP: {e}")
    except zipfile.BadZipFile:
        logging.error(f"Error: {zip_filename} is not a valid zip file.")
    except Exception as e:
        logging.error(f"An error occurred in install_bundle: {e}")
    finally:
        if os.path.exists(zip_file_path):
            os.remove(zip_file_path)
        shutil.rmtree(local_temp_path, ignore_errors=True)

def main():
    config = configparser.ConfigParser()
    config.read('RabbitMQ/RabbitMQ.ini')

    broker_host = config.get('Quality-Assurance', 'BROKER_HOST')
    broker_port = config.getint('Quality-Assurance', 'BROKER_PORT')
    user = config.get('Quality-Assurance', 'USER')
    password = config.get('Quality-Assurance', 'PASSWORD')
    vhost = config.get('Quality-Assurance', 'VHOST')
    queue = config.get('Quality-Assurance', 'QUEUE')

    credentials = pika.PlainCredentials(user, password)

    while True:
        try:
            logging.info(
                f"Attempting to connect to RabbitMQ: {broker_host}:{broker_port} "
                f"on vhost '{vhost}' as user '{user}'"
            )

            connection = pika.BlockingConnection(pika.ConnectionParameters(
                host=broker_host,
                port=broker_port,
                virtual_host=vhost,
                credentials=credentials
            ))

            channel = connection.channel()
            channel.queue_declare(queue=queue, durable=True)
            channel.basic_consume(queue=queue, on_message_callback=process_bundle_message)

            logging.info(f"Connected to RabbitMQ. Waiting for messages on queue '{queue}'.")
            channel.start_consuming()

        except pika.exceptions.AMQPConnectionError as e:
            logging.error(f"Connection to RabbitMQ failed: {e}")
            logging.info("Retrying in 10 seconds...")
            time.sleep(10)
        except Exception as e:
            logging.error(f"An unexpected error occurred during connection: {e}")
            logging.info("Retrying in 10 seconds...")
            time.sleep(10)

if __name__ == "__main__":
    main()

