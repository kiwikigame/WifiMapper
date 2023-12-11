from sqlalchemy import create_engine, Column, String, Float, TIMESTAMP, Integer
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker
from sqlalchemy.sql import func
from geopy.distance import geodesic
import numpy as np


from sqlalchemy.orm import declarative_base

Base = declarative_base()

class WifiBC(Base):
    __tablename__ = 'wifibc'
    Id = Column(Integer, primary_key=True, autoincrement=True)
    Scaner = Column(String(30))
    SSID = Column(String(33), nullable=False)
    Networkmac = Column(String(23), nullable=False)
    TIME = Column(TIMESTAMP, nullable=False, server_default=func.now())
    SIG = Column(Integer, nullable=False)
    LAT = Column(Float(13, 10), nullable=False)
    LON = Column(Float(13, 10), nullable=False)
    Securite = Column(String(50))

class WifiTRG(Base):
    __tablename__ = 'wifitrg'
    Id = Column(Integer, primary_key=True, autoincrement=True)
    DernierScaner = Column(String(30))
    PremierScaner = Column(String(30))
    SSID = Column(String(33), nullable=False)
    Networkmac = Column(String(23), nullable=False)
    SIGMOY = Column(Float)
    Premiereaparition = Column(TIMESTAMP)
    Derniereaparition = Column(TIMESTAMP)
    LAT = Column(Float(13, 10), nullable=False)
    LON = Column(Float(13, 10), nullable=False)
    Securite = Column(String(50))  
    NBBC = Column(Integer)  

engine = create_engine('mysql://kiwiki:sqlsql@192.168.1.150:3306/iotproj')
Session = sessionmaker(bind=engine)
session = Session()

from sqlalchemy import func

beacon_groups = (
    session.query(WifiBC.SSID, WifiBC.Networkmac)
    .group_by(WifiBC.SSID, WifiBC.Networkmac)
    .all()
)

for group in beacon_groups:
    ssid, networkmac = group

    same_beacons = (
        session.query(WifiBC)
        .filter_by(SSID=ssid, Networkmac=networkmac)
        .all()
    )

    if same_beacons:
        positions = np.array([(float(b.LAT), float(b.LON)) for b in same_beacons])
        signals = np.array([(10 ** (b.SIG / 10)) for b in same_beacons])

        weighted_positions = positions * signals[:, np.newaxis]
        avg_position = np.sum(weighted_positions, axis=0) / np.sum(signals)

        avg_signal = np.mean([b.SIG for b in same_beacons])

        securite = same_beacons[0].Securite if same_beacons[0].Securite else None

        nbbc = len(same_beacons)


        premier_scaner = same_beacons[0].Scaner if same_beacons else None
        dernier_scaner = same_beacons[-1].Scaner if same_beacons else None

        wifi_trg = (
            session.query(WifiTRG)
            .filter_by(SSID=ssid, Networkmac=networkmac)
            .first()
        )

        if wifi_trg:
            wifi_trg.Premiereaparition = min([b.TIME for b in same_beacons])
            wifi_trg.Derniereaparition = max([b.TIME for b in same_beacons])
            wifi_trg.LAT = avg_position[0]
            wifi_trg.LON = avg_position[1]
            wifi_trg.PremierScaner = premier_scaner
            wifi_trg.DernierScaner = dernier_scaner
            wifi_trg.SIGMOY = avg_signal
            wifi_trg.Securite = securite
            wifi_trg.NBBC = nbbc
        else:
            wifi_trg = WifiTRG(
                SSID=ssid,
                Networkmac=networkmac,
                Premiereaparition=min([b.TIME for b in same_beacons]),
                Derniereaparition=max([b.TIME for b in same_beacons]),
                LAT=avg_position[0],
                LON=avg_position[1],
                PremierScaner=premier_scaner,
                DernierScaner=dernier_scaner,
                SIGMOY=avg_signal,
                Securite=securite,
                NBBC=nbbc
            )

        session.merge(wifi_trg)

    session.commit()