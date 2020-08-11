000100131107     h dftactgrp(*no) actgrp(*caller)  thread(*serialize)
000101131107      //
000102131107     d crtString       PR              o   EXTPROC(*JAVA:
000103131107     d                                     'java.lang.String':
000104131107     d                                     *CONSTRUCTOR)
000105131107     d RPGBytes                      30A   Const Varying
000106131107      //
000107190417      // Prototype for Java String's getBytes method abcds
000108190417      //
000109131107     d cvtToBytes      PR            30A   EXTPROC(*JAVA:
000110131107     d                                     'java.lang.String':
000111131107     d                                     'getBytes')
000112131107     d                                     Varying
000113131107      //
000114131107      // Item object
000115131107     d Item            s               o   Class(*JAVA:'com.RpgToJava1.Item')
000116131107      //
000117131107      // Prototype for Item object
000118131107     d crtItem         PR              o   EXTPROC(*JAVA:
000119131107     d                                     'com.RpgToJava1.Item':
000120131107     d                                     *CONSTRUCTOR)
000121131107      //
000122131107      // Prototype for Item's Description1 Method
000123131107     d getDesc1        PR              o   EXTPROC(*JAVA:
000124131107     d                                     'com.RpgToJava1.Item':
000125131107     d                                     'getDescription1')
000126131107     d                                     Class (*JAVA:'java.lang.String')
000127131107      //
000128131107      // Prototype for Item's Description1 Method
000129131107     d getDesc2        PR              o   EXTPROC(*JAVA:
000130131107     d                                     'com.RpgToJava1.Item':
000131131107     d                                     'getDescription2')
000132131107     d                                     Class (*JAVA:'java.lang.String')
000133131107      //
000134131107      // Prototype for Item's Description1 Method
000135131107     d getPrice        PR            10I 0 EXTPROC(*JAVA:
000136131107     d                                     'com.RpgToJava1.Item':
000137131107     d                                     'getPrice')
000138131107      //
000139131107      // GetItem object
000140131107     d GetItem         s               o   Class(*JAVA:'com.RpgToJava1.GetItem')
000141131107      //
000142131107      // Prototype for GetItem object
000143131107     d crtGetItem      PR              o   EXTPROC(*JAVA:
000144131107     d                                     'com.RpgToJava1.GetItem':
000145131107     d                                     *CONSTRUCTOR)
000146131107      // Prototype for GetItem's GetItemInfo Method
000147131107     d getItemInfo     PR              o   EXTPROC(*JAVA:
000148131107     d                                     'com.RpgToJava1.GetItem':
000149131107     d                                     'getItemInfo')
000150131107     d                                     Class (*JAVA:'com.RpgToJava1.Item')
000151131107      //
000152131107     d String                          o   Class (*JAVA:'java.lang.String')
000153131107     d                                     Const
000154131107      //
000155131107     d $item_          s               o   Class(*JAVA:'java.lang.String')
000156131107     d $desc1_         s               o   Class(*JAVA:'java.lang.String')
000157131107     d $desc2_         s               o   Class(*JAVA:'java.lang.String')
000158131107      //
000159131107     d $desc1          s             30a
000160131107     d $desc2          s             30a
000161131107     d $price          s             10s 0
000162131107      //
000163131107      //
000164131107      //******************************************************************
000165131107      // *ENTRY PLIST
000166131107      //*******************************************************************
000167131107     D ENTRY_PLIST     PR                  EXTPGM('RPGFREEJV')
000168131107     D                               30A
000169131107     D
000170131107     D ENTRY_PLIST     PI
000171131107     D  $Item                        30A
000172131107     D
000173131107      //
000174131107      // Main Routine
000175131107      //
000176131107     C/free
000177131107
000178131107       $item_ = crtString(%trim($item));
000179131107       Item = crtItem();
000180131107       GetItem = crtGetItem();
000181131107
000182131107       Item = getItemInfo(GetItem:$item_);
000183131107
000184131107       $desc1_ = getDesc1(Item);
000185131107       $desc2_ = getDesc2(Item);
000186131107       $price  = getPrice(Item);
000187131107
000188131107       $desc1 = cvtToBytes($desc1_);
000189131107       $desc2 = cvtToBytes($desc2_);
000190131107
000191131107       dsply ($desc1);
000192131107       dsply ($desc2);
000193131107       dsply ($price);
000194131107
000195131107       *inlr = *on;
000196131107
000197131107       Return;
000198131107
000199131107      /end-free
000200131107      //
000201131107      //
