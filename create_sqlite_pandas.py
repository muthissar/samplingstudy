import pandas as pd
from sqlite3 import connect
from sqlalchemy import create_engine, text
if __name__ == '__main__':
    con = connect('./listener_db.sqlite')
    engine = create_engine('sqlite:///listener_db.sqlite')
    with engine.begin() as con:
        table = pd.read_sql_table('user_table', con=con)
        # pd.read_sql_table('user_table', con='sqlite:///listener_db.sqlite')
        1+1