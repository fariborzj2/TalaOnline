import json
import random
from datetime import datetime, timedelta

def generate_data(base_price, count):
    data = []
    current_price = base_price
    today = datetime.now()
    for i in range(count, -1, -1):
        date = today - timedelta(days=i)
        current_price += random.randint(-100000, 100000)
        data.append({
            "date": date.strftime("%Y-%m-%d"),
            "price": int(current_price)
        })
    return data

prices = {
    "gold": generate_data(19400000, 400),
    "silver": generate_data(18400000, 400)
}

with open("data/prices.json", "w") as f:
    json.dump(prices, f, indent=2)
