import pika
import json
import configparser

def main():
    try:
        with open("last_bundle.txt", "r") as f:
            zip_filename = f.read().strip()

        print(f"Was the bundle '{zip_filename}' installed successfully?")
        print("1 = Passed")
        print("2 = Failed")
        status_choice = input("Enter 1 or 2: ")

        if status_choice == "1":
            status = "passed"
        elif status_choice == "2":
            status = "failed"
        else:
            print("Invalid input. Exiting.")
            return

        bundle_name = zip_filename

        
        config = configparser.ConfigParser()
        config.read('RabbitMQ/RabbitMQ.ini')
        broker_host = config.get('roll', 'BROKER_HOST')
        broker_port = config.getint('roll', 'BROKER_PORT')
        user = config.get('roll', 'USER')
        password = config.get('roll', 'PASSWORD')
        vhost = config.get('roll', 'VHOST')
        exchange = "qa_exchange"

        
        credentials = pika.PlainCredentials(user, password)
        connection = pika.BlockingConnection(pika.ConnectionParameters(
            host=broker_host,
            port=broker_port,
            virtual_host=vhost,
            credentials=credentials
        ))

        channel = connection.channel()
        channel.exchange_declare(exchange=exchange, exchange_type='direct', durable=True)

        message = {
            'action': 'update_status',
            'bundle_name': bundle_name,
            'status': status
        }

        channel.basic_publish(
            exchange=exchange,
            routing_key='request',
            body=json.dumps(message)
        )

        print("Status message sent.")
        connection.close()

    except FileNotFoundError:
        print("Error: last_bundle.txt not found.")
    except Exception as e:
        print(f"An error occurred: {e}")

if __name__ == "__main__":
    main()

