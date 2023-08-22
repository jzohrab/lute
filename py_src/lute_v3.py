from flask import Flask, redirect
from waitress import serve

app = Flask(__name__)

@app.route("/hello", methods=["GET", "POST"])
def hello_world():
    return "<p>Hello, World!</p>"

@app.route('/read/<page>')
def read_page(page):
    return f'<p>READING BOOK {page}'


if __name__ == "__main__":
    print('running at localhost:5000')
    serve(app, host="0.0.0.0", port=5000)
