import pandas as pd
from sqlite3 import connect
from sqlalchemy import create_engine, text
if __name__ == '__main__':
    # con = connect('./listener_db.sqlite')
    engine = create_engine('sqlite:///listener_db.sqlite')
    with engine.begin() as con:
        user_table = pd.read_sql_table('user', con=con, parse_dates=['create_time'])
        likert_table = pd.read_sql_table('likert', con=con, parse_dates=['time'])
        samples_table = pd.read_sql_table('samples', con=con)
        joined = likert_table.merge(samples_table, left_on='sample', right_on='id' )
        
        1+1