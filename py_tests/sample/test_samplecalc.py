from sample import samplecalc

def test_answer():
    print('hello')
    assert samplecalc.inc(3) == 4
