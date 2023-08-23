"""
Sample only to work out references.
"""

from sample import samplecalc

def test_answer():
    "Dummy test only."
    print('hello')
    assert samplecalc.inc(3) == 4
