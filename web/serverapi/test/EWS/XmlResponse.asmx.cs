using System;
using System.Web.Services;
using System.Xml;

namespace WebServiceExample
{
    [WebService(Namespace = "http://example.org/")]
    [WebServiceBinding(ConformsTo = WsiProfiles.BasicProfile1_1)]
    [System.ComponentModel.ToolboxItem(false)]
    public class XmlResponseService : WebService
    {
        [WebMethod]
        public XmlDocument GetXmlResponse()
        {
            // 创建一个新的 XML 文档
            XmlDocument xmlDoc = new XmlDocument();

            // 创建 XML 声明部分
            XmlDeclaration xmlDeclaration = xmlDoc.CreateXmlDeclaration("1.0", "UTF-8", null);
            XmlElement root = xmlDoc.DocumentElement;
            xmlDoc.InsertBefore(xmlDeclaration, root);

            // 创建根元素 <response>
            XmlElement responseElement = xmlDoc.CreateElement(string.Empty, "response", string.Empty);
            xmlDoc.AppendChild(responseElement);

            // 添加子元素 <status>
            XmlElement statusElement = xmlDoc.CreateElement(string.Empty, "status", string.Empty);
            responseElement.AppendChild(statusElement);

            // 添加 <code> 子元素
            XmlElement codeElement = xmlDoc.CreateElement(string.Empty, "code", string.Empty);
            codeElement.InnerText = "200";
            statusElement.AppendChild(codeElement);

            // 添加 <message> 子元素
            XmlElement messageElement = xmlDoc.CreateElement(string.Empty, "message", string.Empty);
            messageElement.InnerText = "Request was successful";
            statusElement.AppendChild(messageElement);

            // 添加数据部分 <data>
            XmlElement dataElement = xmlDoc.CreateElement(string.Empty, "data", string.Empty);
            responseElement.AppendChild(dataElement);

            // 添加用户信息 <user>
            XmlElement userElement = xmlDoc.CreateElement(string.Empty, "user", string.Empty);
            dataElement.AppendChild(userElement);

            XmlElement idElement = xmlDoc.CreateElement(string.Empty, "id", string.Empty);
            idElement.InnerText = "12345";
            userElement.AppendChild(idElement);

            XmlElement nameElement = xmlDoc.CreateElement(string.Empty, "name", string.Empty);
            nameElement.InnerText = "John Doe";
            userElement.AppendChild(nameElement);

            return xmlDoc;
        }
    }
}
