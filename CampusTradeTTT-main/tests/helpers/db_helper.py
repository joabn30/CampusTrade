import os
import random
import mysql.connector
from dotenv import load_dotenv
from pathlib import Path

# Load the .env file from the project root
env_path = Path(__file__).resolve().parents[2] / ".env"
load_dotenv(env_path)


def get_test_account():
    connection = mysql.connector.connect(
        host="127.0.0.1",
        user=os.getenv("DB_USER"),
        password=os.getenv("DB_PASSWORD", ""),
        database=os.getenv("DB_NAME"),
        port=3306
    )

    try:
        cursor = connection.cursor()

        # Check DB
        cursor.execute("SELECT DATABASE()")
        print("Connected database:", cursor.fetchone()[0])

        cursor.execute("SHOW TABLES")
        print("Available tables:", cursor.fetchall())
        # Update table/column names to match your schema.
        cursor.execute("""
            SELECT email
            FROM users
            WHERE email LIKE %s
        """, ("%@go.minnstate.edu",))

        emails = [row[0] for row in cursor.fetchall()]

        if not emails:
            raise ValueError("No registered test accounts found.")

        return {
            "email": random.choice(emails),
            "password": "Kenya@12"
        }

    finally:
        cursor.close()
        connection.close()

if __name__ == "__main__":
    account = get_test_account()
    print(account["email"])