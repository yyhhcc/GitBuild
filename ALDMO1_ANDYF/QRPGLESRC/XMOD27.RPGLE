000100001027     H*
000200001027     H********************************************************************
000300001027     H** PROGRAM NAME: XMOD27                                           *
000400001027     H**                                                                 *
000500001027     H********************************************************************
000600001027     H*
000700001027     F*
000800001027     FQSYSPRT   O    F  132        PRINTER OFLIND(*INOA)
000900001027     F*
001000001027     D DSPMSG          S             30    DIM(7) CTDATA PERRCD(2)
001100001027     D*
001200001027     D                 DS
001300001027     D  CNT                    1      2  0
001400001027     D  SW0                    3      3
001500001027     D  PARM1                  4     13
001600001027     D  PARM2                 14     17  0
001700001027     D  WRKCNT                18     19  0
001800001027     I*
001900001027     C*
002000001027     C                   MOVE      DSPMSG(1)     OUTMSG           30
002100001027     C                   EXCEPT    MSGS
002200001027     C                   MOVEL     *BLANKS       PARM1
002300001027     C                   MOVE      *ZERO         PARM2
002400001027     C                   DO        10            CNT
002500001027     C                   EXSR      CHKCNT
002600001027     C                   END
002700001027     C                   CALLB     'XMOD25'
002800001027     C                   PARM                    PARM1
002900001027     C                   PARM                    PARM2
003000001027     C     SW0           IFEQ      '1'
003100001027     C                   MOVE      DSPMSG(2)     OUTMSG           30
003200001027     C                   EXCEPT    MSGS
003300001027     C                   END
003400001027     C     PARM1         IFNE      'CORRECT'
003500001027     C                   MOVE      PARM1         OUTMSG           30
003600001027     C                   EXCEPT    MSGS
003700001027     C                   END
003800001027     C                   SETON                                        LR
003900001027     C                   RETURN
004000001027     C*
004100001027     C     CHKCNT        BEGSR
004200001027     C     CNT           IFEQ      3
004300001027     C     CNT           OREQ      7
004400001027     C     CNT           IFEQ      3
004500001027     C                   MOVE      DSPMSG(3)     OUTMSG           30
004600001027     C                   EXCEPT    MSGS
004700001027     C                   ELSE
004800001027     C                   MOVE      DSPMSG(4)     OUTMSG           30
004900001027     C                   EXCEPT    MSGS
005000001027     C                   END
005100001027     C                   ELSE
005200001027     C     CNT           DIV       2             WRKCNT
005300001027     C                   MULT      2             WRKCNT
005400001027     C     CNT           IFEQ      WRKCNT
005500001027     C     CNT           IFEQ      0
005600001027     C                   MOVE      DSPMSG(5)     OUTMSG           30
005700001027     C                   EXCEPT    MSGS
005800001027     C                   ELSE
005900001027     C                   MOVE      DSPMSG(6)     OUTMSG           30
006000001027     C                   EXCEPT    MSGS
006100001027     C                   END
006200001027     C                   ELSE
006300001027     C                   MOVE      DSPMSG(7)     OUTMSG           30
006400001027     C                   EXCEPT    MSGS
006500001027     C                   END
006600001027     C                   END
006700001027     C                   ENDSR
006800001027     C*
006900001027     O*
007000001027     OQSYSPRT   E            MSGS           1
007100001027     O                       OUTMSG              31
007200001027     OQSYSPRT   E    OA      MSGS           1
007300001027     O                                           16 '               '
007400001027     O                                           16 '               '
007500001027**  DSPMSG
007600001027BEGINNING SAMPLE EXECUTION    SOMEBODY TURNED THE SWITCH OFF
007700001027COUNT = THREE                 COUNT = SEVEN
007800001027COUNT IS ZERO                 COUNT IS EVEN
007900001027COUNT IS ODD
