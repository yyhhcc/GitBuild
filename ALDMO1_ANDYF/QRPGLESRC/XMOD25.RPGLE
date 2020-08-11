000100001027     H*
000200001027     H********************************************************************
000300001027     H** PROGRAM NAME: XMOD25                                    *
000400001027     H**                                                                 *
000500001027     H********************************************************************
000600001027     H*
000700001027     F*
000800001027     FQSYSPRT   O    F  132        PRINTER OFLIND(*INOA)
000900001027     F*
001000001027     D DSPMSG          S             30    DIM(4) CTDATA PERRCD(2)
001100001027     D*
001200001027     D                 DS
001300001027     D  CNT                    1      2  0 INZ
001400001027     D  WRKCNT                18     19  0 INZ
001500001027     I*
001600001027     C                   MOVE      DSPMSG(1)     OUTMSG           30
001700001027     C                   EXCEPT    MSGS
001800001027     C                   DO        10            CNT
001900001027     C                   EXSR      CHKCNT
002000001027     C                   END
002100001027     C                   SETON                                        LR
002200001027     C                   RETURN
002300001027     C*
002400001027     C     CHKCNT        BEGSR
002500001027     C     CNT           DIV       2             WRKCNT
002600001027     C                   MULT      2             WRKCNT
002700001027     C     CNT           IFEQ      WRKCNT
002800001027     C     CNT           IFEQ      0
002900001027     C                   MOVE      DSPMSG(2)     OUTMSG           30
003000001027     C                   EXCEPT    MSGS
003100001027     C                   ELSE
003200001027     C                   MOVE      DSPMSG(3)     OUTMSG           30
003300001027     C                   EXCEPT    MSGS
003400001027     C                   END
003500001027     C                   END
003600001027     C   LR              MOVE      DSPMSG(4)     OUTMSG           30
003700001027     C   LR              EXCEPT    MSGS
003800001027     C                   ENDSR
003900001027     O*
004000001027     OQSYSPRT   E            MSGS           1
004100001027     O                       OUTMSG              31
004200001027     OQSYSPRT   E    OA      MSGS           1
004300001027     O                                           16 '               '
004400001027     O                                           16 '               '
004500001027**  DSPMSG
004600001027BEGINNING SAMPLE EXECUTION    COUNT IS ZERO
004700001027COUNT IS EVEN                 LR IS ON
