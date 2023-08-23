"""
Lute v3 entry point.
Starts waitress and runs flask app.
"""

from flask import Flask
from waitress import serve

app = Flask(__name__)

@app.route("/hello", methods=["GET", "POST"])
def hello_world():
    "Dummy method to verify flask running."
    return "<p>Hello, World!</p>"

@app.route('/read/<textid>')
def read_page(textid):
    "Display text with id for reading."
    return f'<p>READING BOOK {textid}'


if __name__ == "__main__":
    print('running at localhost:5000')
    serve(app, host="0.0.0.0", port=5000)
