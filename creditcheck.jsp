<%--   Tax Update SS-95 --%>
			
<%@ taglib uri="http://java.sun.com/portlet" prefix="portlet" %>
<portlet:defineObjects />
<%@page language="java" import="creditcheckCommand"%>
<%@page language="java" import="java.util.Map"%>

		<h3>creditcheck Transaction</h3>
			
<%! 
	private boolean executeButtonPressed;
	private Map params;
%>
<% 
	executeButtonPressed = ((Boolean)renderRequest.getAttribute("executeButtonPressed")).booleanValue();
	params = (Map)renderRequest.getAttribute("params");
%>

<form method="post" action="<portlet:actionURL/>">
	Enter values for input parameters:

		
        custnum: <INPUT value="aStringValue" name="custnum_param" type="text"><br>
    
			
	<input class="button" type="submit" value="Execute"/>
</form>

<%	if (executeButtonPressed) {	%>

<p>Results of execution of creditcheck
<jsp:useBean id="commandBean" class="creditcheckCommand" scope="request"/>
<% 
                if (!commandBean.isExecuted()) {
        %><jsp:setProperty name="commandBean" property="arg_custnum" value="<%=((String[])params.get("custnum_param"))[0]%>"/><%
    
			
			commandBean.execute();
		}
		if (commandBean.isExecuted()) {
%>
		
        <br>
            Result$RESULT: <jsp:getProperty name="commandBean" property="result_Result$RESULT"/><br>
            Result$baldue: <jsp:getProperty name="commandBean" property="result_Result$baldue"/><br>
            Result$creditlimit: <jsp:getProperty name="commandBean" property="result_Result$creditlimit"/><br>
            <% for ( int i = 0; i < commandBean.getResultSize_Result$outlistSequence(); i++ ) { %>Result$outlistSequence[<%= i %>]:
                <br>
                    Result$outlistSequence$ordernum: <%= commandBean.getResult_Result$outlistSequence$ordernum(i) %><br>
                    Result$outlistSequence$orddt: <%= commandBean.getResult_Result$outlistSequence$orddt(i) %><br>
                    Result$outlistSequence$amt: <%= commandBean.getResult_Result$outlistSequence$amt(i) %><br>
                
            <br><% } %>
        
    
<%              }       %>
<%      }       %>

		