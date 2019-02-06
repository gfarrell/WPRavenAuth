#!/usr/bin/python

# --------------------------------------------------------------------------
# Copyright (c) 2012, University of Cambridge Computing Service
#
# This file is part of the Lookup/Ibis client library.
#
# This library is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published
# by the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This library is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
# or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Lesser General Public
# License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with this library.  If not, see <http://www.gnu.org/licenses/>.
#
# Author: Dean Rasheed (dev-group@ucs.cam.ac.uk)
# --------------------------------------------------------------------------

usage = """
generate-client-methods
-----------------------

Generate the client method classes from the Lookup/Ibis application.wadl.

The generated code relies on specific hand-written code in separate files
to actually send requests to the server and decode the results. The
auto-generated client methods contain the details of all the API methods,
including their parameters, documentation and the URLs on the server
needed to invoke them.

Usage: generate-client-methods [options] <wadl_file>

Where <wadl_file> is the location of the application.wadl file.
The following options are supported:

    -lang <language>    Specify the Language of the generated code. This
                        may be any of the following:

                            * "java" (the default)
                            * "php"
                            * "python"
                            * "python3"

    -d <output_dir>     The directory in which to write the output code.
                        This defaults to the current directory.

NOTE: The generated code files are only touched if they actually need to
be modified. Otherwise their original timestamps are preserved.

"""

import re
import os
import sys
import xml.dom.minidom
from xml.dom import *

lang = "java"
out_dir = "."
wadl_file = None

def error(msg):
    sys.stderr.write("ERROR: %s\n" % msg)
    sys.exit(1)

# ==========================================================================
# Code to read and parse the WADL file.
# ==========================================================================

class Param:
    """
    A class representing a method parameter. The java_type is a custom field
    added to the WADL file, reflecting the parameter's datatype on the server.
    The datatype on the client may be different (e.g., the client uses String
    instead of List<String>).
    """
    def __init__(self, node):
        """
        Create a Param instance from a <param> node in the WADL file. This may
        be a path parameter, a query parameter or a form parameter.
        """
        self.kind = node.getAttribute("type")
        self.name = node.getAttribute("name")
        self.java_type = node.getAttribute("javaType")

class Method:
    """
    A class representing an API method contained in a second-level resource
    from the WADL file. Note that there may be multiple methods in a second-
    level resource node (if they have the same path and path parameters).
    """
    def __init__(self, node, cls, idx):
        """
        Create a Method instance from a second-level <resource> node in the
        WADL file. The idx argument specifies which method to use (0, 1, ...)
        in the case where the <resource> node contains multiple <method>
        nodes.
        """
        # Get the method path and prefix it with the class's path
        path1 = cls.path
        if path1.startswith("/"): path1 = path1[1:]
        if path1.endswith("/"): path1 = path1[:-1]

        path2 = node.getAttribute("path")
        if path2.startswith("/"): path2 = path2[1:]

        self.path = path1 + "/" + path2

        # Look for path <param> nodes and the required <method> node
        self.path_params = []
        method_idx = 0
        method = None
        for child in node.childNodes:
            if child.nodeType == Node.ELEMENT_NODE:
                if child.tagName == "param":
                    self.path_params.append(Param(child))
                elif child.tagName == "method":
                    if method_idx == idx: method = child
                    method_idx += 1

        # Get the method name and kind (GET, POST, PUT or DELETE)
        if method == None:
            error("Failed to find <method> node under <resource> node")

        self.name = method.getAttribute("id")
        self.kind = method.getAttribute("name")

        # Get the method result field and type (colon separated)
        self.result_field = method.getAttribute("resultField")
        idx = self.result_field.find(":")
        if idx == -1:
            error("Invalid method result field '%s'" % self.result_field)

        self.result_type = self.result_field[idx+1:]
        self.result_field = self.result_field[:idx]

        # Find the method docs, if any
        self.docs = ""
        for child in method.childNodes:
            if child.nodeType == Node.ELEMENT_NODE and\
               child.tagName == "doc":
                self.docs = child.firstChild.nodeValue
                break

        # Look for a <request> child node (holds any non-path parameters)
        request = None
        for child in method.childNodes:
            if child.nodeType == Node.ELEMENT_NODE and\
               child.tagName == "request":
                if request != None:
                    error("Can't handle multiple <request> nodes under "\
                          "a single <method> node")
                request = child

        # Process any query parameters in the <request> node, and look
        # out for a <representation> node holding form parameters
        self.query_params = []
        representation = None
        if request != None:
            for child in request.childNodes:
                if child.nodeType == Node.ELEMENT_NODE:
                    if child.tagName == "param":
                        self.query_params.append(Param(child))
                    elif child.tagName == "representation":
                        if representation != None:
                            error("Can't handle multiple <representation> "\
                                  "nodes under a <request> node")
                        representation = child

        # Finally process any form parameters in the <representation> node
        self.form_params = []
        if representation != None:
            for child in representation.childNodes:
                if child.nodeType == Node.ELEMENT_NODE and\
                   child.tagName == "param":
                    self.form_params.append(Param(child))

        self.all_params = self.path_params +\
                          self.query_params + self.form_params

    def get_docs(self):
        """
        Get the documentation for this method (decorated with the method's
        HTTP method and path).
        """
        required_query_params = []
        for param in self.query_params:
            if ("@param %s [required]" % param.name) in self.docs:
                required_query_params.append("%s=..." % param.name)

        if required_query_params:
            doc_params = "?%s" % ("&".join(required_query_params))
        else:
            doc_params = ""

        style = "background-color: #eec;"
        extra_docs = '<code style="%s">[ HTTP: %s /%s%s ]</code>'\
                     % (style, self.kind, self.path, doc_params)

        if self.docs == "":
            return "    /**\n     * %s\n     */" % extra_docs

        i1 = self.docs.find("@param")
        i2 = self.docs.find("@return")
        if i1 == -1: idx = i2
        elif i2 == -1: idx = i1
        else: idx = min(i1, i2)
        if idx == -1: idx = self.docs.rfind("*/")
        idx = self.docs.rfind("\n", 0, idx)
        if self.docs[:idx].endswith("     *"):
            idx = self.docs.rfind("\n", 0, idx-1)

        return "%s\n     * <p>\n     * %s%s"\
               % (self.docs[:idx], extra_docs, self.docs[idx:])

class MethodClass:
    """
    A class representing a top-level resource from the WADL file.

    This corresponds to a /grails-app/resources/<Xxx>Resource.groovy class
    on the server, and a matching <Xxx>Methods class on the client.
    """
    def __init__(self, node, className, path):
        """
        Create a method class instance from a top-level <resource> node in
        the WADL file.
        """
        self.name = re.sub("Resource$", "Methods", className)
        self.path = path

        # Find the class docs, if any
        self.docs = ""
        for child in node.childNodes:
            if child.nodeType == Node.ELEMENT_NODE and\
               child.tagName == "doc":
                self.docs = child.firstChild.nodeValue
                break

        # Find any methods on the class
        self.methods = []
        for child in node.childNodes:
            if child.nodeType == Node.ELEMENT_NODE and\
               child.tagName == "resource":
                method_idx = 0
                for grandchild in child.childNodes:
                    if grandchild.nodeType == Node.ELEMENT_NODE and\
                       grandchild.tagName == "method":
                        self.methods.append(Method(child, self, method_idx))
                        method_idx += 1

class Application:
    """
    A class representing the entire web service API.

    This is basically just a list of top-level resources, which are
    represented as MethodClass objects.
    """
    def __init__(self, app):
        """
        Create the Application from the top-level <application> node in the
        WADL file, creating all the child objects underneath it.
        """
        self.classes = []

        # Look for the <resources> node
        resources = None
        for child in app.childNodes:
            if child.nodeType == Node.ELEMENT_NODE and\
               child.tagName == "resources":
                resources = child
                break
        if resources == None:
            error("Failed to find '<resources>' node")

        # Process each child <resource> node (class definitions)
        classes_found = set()
        for child in resources.childNodes:
            if child.nodeType == Node.ELEMENT_NODE and\
               child.tagName == "resource":
                path = child.getAttribute("path")
                className = child.getAttribute("className")
                if path != None and className != None:
                    if className in classes_found:
                        error("Found 2 copies of class '%s'" % className)
                    classes_found.add(className)
                    self.classes.append(MethodClass(child, className, path))

# ==========================================================================
# Common code to help generate client code.
# ==========================================================================

LICENCE = """
Copyright (c) 2012, University of Cambridge Computing Service

This file is part of the Lookup/Ibis client library.

This library is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published
by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This library is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Lesser General Public
License for more details.

You should have received a copy of the GNU Lesser General Public License
along with this library.  If not, see <http://www.gnu.org/licenses/>.
"""

def aligned_output(cols, indent, tab_size=4):
    """
    Pretty printing function to output tabular data containing multiple
    columns of text, left-aligned.

    The first column is aligned at an indentation of "indent". Each
    successive column is aligned on a suitable multiple of the "tab_size"
    with spaces for all indentation.

    "cols" is assumed to be a list of columns, with each column holding
    an equal length list of string values.
    """
    # Check the input data
    ncols = len(cols)
    if ncols == 0: return ""
    nrows = len(cols[0])
    if nrows == 0: return ""

    # Work out the indentations and widths of each column
    indents = [ indent ]
    widths = []
    for col in range(1, ncols):
        width = max(len(x) for x in cols[col-1])
        indents.append(((indents[col-1]+width+tab_size) / tab_size) * tab_size)
        widths.append(indents[col] - indents[col-1])

    # Now output the actual tabular values
    result = ""
    for row in range(0, nrows):
        if row > 0:
            result += ",\n" + (" " * indent)
        if len(cols) > 1:
            for col in range(0, ncols-1):
                result += cols[col][row].ljust(widths[col])
        result += cols[-1][row]
    return result

def list_items_to_text(docs):
    """
    Convert HTML list items to plain text.

    The result is in reST(reStructuredText) format, which is suitable for
    Python's Sphinx documentation generator, and is also very human readable.
    """
    docs = docs.strip()

    # Remove any <ul> tags (the <li> tags are all we need)
    docs = re.sub("</?ul[^>]*>", "", docs)

    # Iterate through all the <li> start and end tags, tracking the nested
    # list depth (-1 => not in a list, 0 => in top-level list, ...)
    result = ''
    depth = -1
    end_idx = 0

    for li_match in re.finditer(re.compile("</?li[^>]*>"), docs):
        li_start = li_match.start()
        li_end = li_match.end()
        li_text = li_match.group()

        # Add on the next segment of text. If we're in a list, remove any
        # other HTML tags it contains so list items are plain text.
        segment = docs[end_idx:li_start].strip()
        if depth >= 0: segment = re.sub("<[^>]+>", "", segment)

        if segment:
            if depth >= 0:
                # We're in a list, so add a bullet point marker to the first
                # line and align any later lines with the first line's text
                segment = re.sub("(?m)^\\s*", "  ", segment)
                segment = "* " + segment[2:]

                # Add more indentation according to the list nesting depth
                if depth > 0: segment = re.sub("(?m)^", "  "*depth, segment)

            # Add the segment, with a blank line before (and later, after)
            # for compatibility with Sphinx
            if result: result += "\n\n"
            result += segment
        end_idx = li_end

        # Track the list nesting depth
        if li_text.startswith("<li"): depth += 1
        elif depth >= 0: depth -= 1

    # Add the final segment (assumed to not be in a list)
    segment = docs[end_idx:].strip()
    if segment:
        if result: result += "\n\n"
        result += segment

    return result

def comment_out(text, comment="#"):
    """
    Comment out some text, using the specified comment character(s) at the
    start of each line.
    """
    text = text.strip()

    result = ""
    for line in text.split("\n"):
        if line: result += comment+" "+line+"\n"
        else: result += comment+"\n"

    return result.strip()

def update_file_if_changed(filename, contents):
    """
    Update a file using the specified contents. We deliberately avoid touching
    the file if it's contents haven't changed, so that re-compiles aren't
    triggered unnecessariliy, and tar/jar files don't need updating if nothing
    has really changed.
    """
    if len(content) < 10:
        error("Failed to generate valid file contents")

    # Any existing file contents
    try:
        f = open(filename, "rb")
        try:
            old_content = f.read()
        finally:
            f.close()
    except IOError:
        old_content = None

    # Write the file if it has changed (or didn't exist)
    if content != old_content:
        f = open(filename, "wb")
        try:
            f.write(content)
            print "%s ... *** UPDATED ***" % filename
        finally:
            f.close()
    else:
        print "%s ... unchanged" % filename

# ==========================================================================
# Code to generate the Java client classes.
# ==========================================================================

JAVA_METHOD_TEMPLATE = """%(method_docs)s
    public %(result_type)s %(method_name)s(%(method_params)s)
        throws IbisException, IOException, JAXBException
    {
        String[] pathParams = { %(path_params)s };
        Object[] queryParams = { %(query_params)s };
        Object[] formParams = { %(form_params)s };
        IbisResult result = conn.invokeMethod(Method.%(method_kind)s,
                                              "%(method_path)s",
                                              pathParams,
                                              queryParams,
                                              formParams);
        if (result.error != null)
            throw new IbisException(result.error);
        return %(result)s;
    }
"""

def get_java_param_type(param):
    """
    Returns the Java type to use for a parameter. Note that this is not
    necessarily the same Java type as in the matching server method (for
    example List<String> on the server, is just String in the client).
    """
    if param.java_type == "boolean":           return "boolean"
    if param.java_type == "int":               return "int"
    if param.java_type == "long":              return "long"
    if param.java_type == "java.lang.Boolean": return "Boolean"
    if param.java_type == "java.lang.Integer": return "Integer"
    if param.java_type == "java.lang.Long":    return "Long"
    if param.java_type == "java.lang.String":  return "String"
    if param.java_type == "java.util.Date":    return "java.util.Date"
    if param.java_type == "java.util.List":    return "String" # Special case

    if param.java_type.startswith("uk.ac.cam.ucs.ibis.dto."):
        return param.java_type[23:]

    error("Unsupported parameter type: '%s' (javaType: '%s')"\
          %(param.kind, param.java_type))

def java_param_to_string(param):
    """
    Cast a parameter to a string. This is necessary for path parameters,
    which must be strings.
    """
    if get_java_param_type(param) == "String": return param.name
    return '""+%s' % param.name

def generate_java_method(method):
    """
    Generate the Java code for a single web service API method, using the
    JAVA_METHOD_TEMPLATE.
    """
    # Method parameters
    param_types = [ get_java_param_type(x) for x in method.all_params ]
    param_names = [ x.name for x in method.all_params ]
    indent = 13 + len(method.result_type) + len(method.name)
    method_params = aligned_output([param_types, param_names], indent)

    # Path parameters
    path_params = ", ".join(java_param_to_string(x) for x in method.path_params)

    # Query parameters
    param_pairs = [ '"'+x.name+'", '+x.name for x in method.query_params ]
    query_params = aligned_output([param_pairs], 33)

    # Form parameters
    param_pairs = [ '"'+x.name+'", '+x.name for x in method.form_params ]
    form_params = aligned_output([param_pairs], 32)

    # Method path - replace any placeholders with Java-style format specifiers
    path = method.path
    param_number = 1
    while re.search("[{][^}]+[}]", path):
        path = re.sub("[{][^}]+[}]", "%%%d$s" % param_number, path, 1)
        param_number += 1

    # Final method result (only int and boolean value fields need to be
    # coerced into the required type)
    if method.result_type == "boolean":
        result = "Boolean.parseBoolean(result.value)"
    elif method.result_type == "int":
        result = "Integer.parseInt(result.value)"
    else:
        result = "result.%s" % method.result_field

    return JAVA_METHOD_TEMPLATE % { "method_docs": method.get_docs(),
                                    "result_type": method.result_type,
                                    "method_name": method.name,
                                    "method_params": method_params,
                                    "path_params": path_params,
                                    "query_params": query_params,
                                    "form_params": form_params,
                                    "method_kind": method.kind,
                                    "method_path": path,
                                    "result": result }

JAVA_CLASS_TEMPLATE = """/* === AUTO-GENERATED - DO NOT EDIT === */

/*%(licence)s*/
package uk.ac.cam.ucs.ibis.methods;

import java.io.IOException;

import javax.xml.bind.JAXBException;

import uk.ac.cam.ucs.ibis.client.ClientConnection;
import uk.ac.cam.ucs.ibis.client.ClientConnection.Method;
import uk.ac.cam.ucs.ibis.client.IbisException;
import uk.ac.cam.ucs.ibis.dto.*;
%(class_docs)s
public class %(class_name)s
{
    // The connection to the server
    private ClientConnection conn;

    /**
     * Create a new %(class_name)s object.
     *
     * @param conn The ClientConnection object to use to invoke methods
     * on the server.
     */
    public %(class_name)s(ClientConnection conn)
    {
        this.conn = conn;
    }
%(methods)s}
"""

def generate_java_class(cls):
    """
    Generate the Java code for a single XxxMethods class, using the
    JAVA_CLASS_TEMPLATE.
    """
    methods = "".join(generate_java_method(x) for x in cls.methods)

    return JAVA_CLASS_TEMPLATE % { "licence": LICENCE,
                                   "class_docs": cls.docs,
                                   "class_name": cls.name,
                                   "methods": methods }

# ==========================================================================
# Code to generate the Python methods module.
# ==========================================================================

PYTHON_METHOD_TEMPLATE = '''    def %(method_name)s(%(method_params)s):
        """
%(method_docs)s
        """
        path = "%(method_path)s"
        path_params = {%(path_params)s}
        query_params = {%(query_params)s}
        form_params = {%(form_params)s}
        result = self.conn.invoke_method("%(method_kind)s", path, path_params,
                                         query_params, form_params)
        if result.error:
            raise IbisException(result.error)
        return %(result)s
'''

def get_python_param_type(param):
    """
    Returns the python type of a parameter (for documentation only).
    """
    if param.java_type == "boolean":           return "bool"
    if param.java_type == "int":               return "int"
    if param.java_type == "long":              return "long"
    if param.java_type == "java.lang.Boolean": return "bool"
    if param.java_type == "java.lang.Integer": return "int"
    if param.java_type == "java.lang.Long":    return "long"
    if param.java_type == "java.lang.String":  return "str"
    if param.java_type == "java.util.Date":    return "date"
    if param.java_type == "java.util.List":    return "str" # Special case

    if param.java_type.startswith("uk.ac.cam.ucs.ibis.dto."):
        return ":any:`" + param.java_type[23:] + "`"

    error("Unsupported parameter type: '%s' (javaType: '%s')"\
          %(param.kind, param.java_type))

def get_python_return_type(method):
    """
    Returns the python return type of a method (for documentation only).
    """
    java_type = method.result_type
    python_type = ""

    if method.result_type.startswith("java.util.List<"):
        java_type = java_type[15:-1]
        python_type = "list of "

    if java_type == "boolean":           return python_type + "bool"
    if java_type == "int":               return python_type + "int"
    if java_type == "long":              return python_type + "long"
    if java_type == "Boolean":           return python_type + "bool"
    if java_type == "Integer":           return python_type + "int"
    if java_type == "Long":              return python_type + "long"
    if java_type == "String":            return python_type + "str"
    if java_type == "Date":              return python_type + "date"
    if java_type == "java.lang.Boolean": return python_type + "bool"
    if java_type == "java.lang.Integer": return python_type + "int"
    if java_type == "java.lang.Long":    return python_type + "long"
    if java_type == "java.lang.String":  return python_type + "str"
    if java_type == "java.util.Date":    return python_type + "date"

    return python_type + ":any:`" + java_type + "`"

def javadocs_to_pydocs(docs, cls, line_prefix="", method=None):
    """
    Convert some javadocs to pydocs. This is plain text with some reST
    (reStructuredText) markup.
    """
    docs = docs.strip()

    # Remove start and end javadoc comments
    if docs.startswith("/**"): docs = docs[3:].strip()
    if docs.endswith("*/"): docs = docs[:-2].strip()

    # Remove any comment line prefixes
    docs = re.sub("(?m)^\\s*[*][ ]?", "", docs)

    # == HTML tag processing ==

    # Replace HTML headings with reST headings
    docs = re.sub("(?s)<h[1-5][^>]*>(.+?)</h[1-5]>", "**\\1**", docs)

    # Replace <b>XXX</b> with **XXX**
    docs = re.sub("(?s)<b>(.+?)</b>", "**\\1**", docs)

    # Replace simple <code>XXX</code> blocks with `XXX`, when XXX looks like
    # a paramter or field name
    docs = re.sub("<code[^>]*>([^\\s\"]+?)</code>", "`\\1`", docs)

    # Replace other single-line <code>XXX</code> blocks with ``XXX``
    docs = re.sub("<code[^>]*>(.+?)</code>", "``\\1``", docs)

    # Replace all other <code> blocks with reST code-blocks
    code_pattern = re.compile("(?s)<code[^>]*>(.+?)</code>")
    code_match = code_pattern.search(docs)
    while code_match:
        code_text = code_match.group(1).strip()
        code_text = re.sub("(?m)^", "  ", code_text)
        code_text = "\n.. code-block:: python\n\n" + code_text
        docs = docs[:code_match.start()] + code_text + docs[code_match.end():]
        code_match = code_pattern.search(docs, code_match.start()+1)

    # Similarly, replace <pre> blocks with reST code-blocks
    pre_pattern = re.compile("(?s)<pre[^>]*>(.+?)</pre>")
    pre_match = pre_pattern.search(docs)
    while pre_match:
        pre_text = pre_match.group(1).strip()
        pre_text = re.sub("(?m)^", "  ", pre_text)
        pre_text = "\n.. code-block:: python\n\n" + pre_text
        docs = docs[:pre_match.start()] + pre_text + docs[pre_match.end():]
        pre_match = pre_pattern.search(docs, pre_match.start()+1)

    # Replace <li> with plain text "*" bullet points (reST format)
    docs = list_items_to_text(docs)

    # Remove any other HTML tags
    docs = re.sub("<[^>]+>", "", docs)

    # == End of HTML tag processing ==

    # == Javadoc tag processing ==

    # Replace named method links of the form  {@link #XXX YYY} with reST
    # references of the form :any:`YYY <${cls.name}.XXX>`.
    #
    # Note that we must explicitly include the class name in the target reST
    # reference, since the Sphinx does not correctly find the right method
    # when there are multiple classes in the same module with methods of the
    # same name.
    docs = re.sub("[{]@link\\s+#([^}\\s]+)\\s+([^}]+)[}]",
                  ":any:`\\2 <"+cls.name+".\\1>`", docs)

    # Similiarly, replace method links of the form {@link #XXX} with reST
    # references of the form :any:`${cls.name}.XXX()`.
    #
    # We explicitly add the parentheses, since Sphinx does not do this by
    # default.
    docs = re.sub("[{]@link\\s+#([^}]+)[}]", ":any:`"+cls.name+".\\1()`", docs)

    # Replace any remaining named links of the form {@link XXX YYY} with reST
    # references of the form :any:`YYY <XXX>`.
    docs = re.sub("[{]@link\\s+([^}\\s]+)\\s+([^}]+)[}]", ":any:`\\2 <\\1>`", docs)

    # Then replace any remaining links of the form {@link XXX} with reST
    # references of the form :any:`XXX`.
    docs = re.sub("[{]@link\\s+([^}]+)[}]", ":any:`\\1`", docs)

    # Replace {@code null} with :any:`None`
    docs = re.sub("[{]@code\\s+null[}]", ":any:`None`", docs)

    # Replace {@code true} with :any:`True`
    docs = re.sub("[{]@code\\s+true[}]", ":any:`True`", docs)

    # Replace {@code false} with :any:`False`
    docs = re.sub("[{]@code\\s+false[}]", ":any:`False`", docs)

    # Replace simple {@code XXX} tags with `XXX`, when XXX looks like a
    # paramter or field name
    docs = re.sub("[{]@code\\s+([^}\\s\"]+)[}]", "`\\1`", docs)

    # Replace all remaining {@code XXX} tags with ``XXX``
    docs = re.sub("[{]@code\\s+([^}]+)[}]", "``\\1``", docs)

    # Replace {@literal XXX} with XXX (no special handling)
    docs = re.sub("[{]@literal\\s+([^}]+)[}]", "\\1", docs)

    # Replace @author with a reST codeauthor directive
    docs = re.sub("@author\\s+(.*)$", ".. codeauthor:: \\1", docs)

    # Convert note paragraphs to reST format
    note_pattern = re.compile("(?s)NOTE:\\s+(.*?)(?=(\n\n|$))")
    note_match = note_pattern.search(docs)
    while note_match:
        note_text = note_match.group(1).strip()
        note_text = re.sub("(?m)^", "  ", note_text)
        note_text = ".. note::\n" + note_text
        docs = docs[:note_match.start()] + note_text + docs[note_match.end():]
        note_match = note_pattern.search(docs, note_match.start()+1)

    # If this is a method's docs, deal with any parameters or returns docs
    if method:
        # Add a parameters section heading, if there are any parameters
        docs = re.sub("(?s)(@param .*)", "**Parameters**\n\\1", docs)

        # Convert each parameter to a format the can be handled by Sphinx
        param_pattern = re.compile("""(?sx)
            @param\\s+([^\\s]+)\\s+             # Parameter name
            (.*?)(?=(@param|@return|@throw|$))  # Parameter docs
            """)

        param_match = param_pattern.search(docs)
        while param_match:
            # Get the parameter's name and type
            param_name = param_match.group(1)
            param_type = None
            for param in method.all_params:
                if param.name == param_name:
                    param_type = get_python_param_type(param)
                    break

            # Construct the new parameter docs
            param_docs = param_match.group(2).strip()
            param_docs = re.sub("(?m)^", "    ", param_docs)
            param_docs = "  `" + param_name + "` : " + param_type +\
                         "\n" + param_docs + "\n\n"

            # Replace the old parameter docs
            docs = docs[:param_match.start()] +\
                   param_docs + docs[param_match.end():]

            # Move on to the next parameter
            param_match = param_pattern.search(docs, param_match.start()+1)

        # Convert the return documentation to a similar format
        return_pattern = re.compile("(?s)@return\\s+(.*?)(?=(@throw|$))")

        return_match = return_pattern.search(docs)
        if return_match:
            return_docs = return_match.group(1).strip()
            return_docs = re.sub("(?m)^", "    ", return_docs)
            return_docs = "**Returns**\n  " +\
                          get_python_return_type(method) +\
                          "\n" + return_docs
            docs = docs[:return_match.start()] +\
                   return_docs + docs[return_match.end():]

    # Add the specified line prefix to each line
    if line_prefix: docs = re.sub("(?m)^", line_prefix, docs)

    # Strip off end-of-line whitespace
    docs = re.sub("(?m)\\s+$", "\n", docs)

    return docs

def generate_python_method(cls, method):
    """
    Generate the Python code for a single web service API method, using the
    PYTHON_METHOD_TEMPLATE.
    """
    # Method parameters
    last_required_param = 0
    for idx, param in enumerate(method.all_params):
        if ("@param %s [required]" % param.name) in method.docs:
            last_required_param = idx

    param_names = ["self"]
    for idx, param in enumerate(method.all_params):
        if idx > last_required_param:
            param_names.append(param.name+"=None")
        else:
            param_names.append(param.name)
    method_params = aligned_output([param_names], 9 + len(method.name))

    # Method docs in plain text
    docs = javadocs_to_pydocs(method.get_docs(), cls, "        ", method)

    # Path parameters
    param_pairs = [ '"'+x.name+'": '+x.name for x in method.path_params ]
    path_params = aligned_output([param_pairs], 23)

    # Query parameters
    param_pairs = [ '"'+x.name+'": '+x.name for x in method.query_params ]
    query_params = aligned_output([param_pairs], 24)

    # Form parameters
    param_pairs = [ '"'+x.name+'": '+x.name for x in method.form_params ]
    form_params = aligned_output([param_pairs], 23)

    # Method path - replace any placeholders with Python format specifiers
    path = re.sub("[{]([^}]+)[}]", "%(\\1)s", method.path)

    # Final method result (only int and boolean value fields need to be
    # coerced into the required type)
    if method.result_type == "boolean":
        result = "result.value and result.value.lower() == \"true\""
    elif method.result_type == "int":
        result = "int(result.value)"
    else:
        result = "result.%s" % method.result_field

    return PYTHON_METHOD_TEMPLATE % { "method_name": method.name,
                                      "method_params": method_params,
                                      "method_docs": docs,
                                      "path_params": path_params,
                                      "query_params": query_params,
                                      "form_params": form_params,
                                      "method_kind": method.kind,
                                      "method_path": path,
                                      "result": result }

PYTHON_CLASS_TEMPLATE = '''class %(class_name)s:
    """
%(class_docs)s
    """
    def __init__(self, conn):
        self.conn = conn

%(methods)s'''

def generate_python_class(cls):
    """
    Generate the Python code for a single XxxMethods class, using the
    PYTHON_CLASS_TEMPLATE.
    """
    docs = javadocs_to_pydocs(cls.docs, cls, "    ")
    methods = "\n".join(generate_python_method(cls, x) for x in cls.methods)

    return PYTHON_CLASS_TEMPLATE % { "class_name": cls.name,
                                     "class_docs": docs,
                                     "methods": methods }

PYTHON_MODULE_TEMPLATE = '''# === AUTO-GENERATED - DO NOT EDIT ===

# --------------------------------------------------------------------------
%(licence)s
# --------------------------------------------------------------------------

"""
Web service API methods. This module is fully auto-generated, and contains
the Python equivalent of the `XxxMethods` Java classes for executing all API
methods.
"""

from connection import IbisException

%(classes)s'''

def generate_python_module(app):
    """
    Generate the Python code for the entire methods module, containing all
    the XxxMethods classes, using the PYTHON_MODULE_TEMPLATE.
    """
    classes = "\n".join(generate_python_class(x) for x in app.classes)

    return PYTHON_MODULE_TEMPLATE % { "licence": comment_out(LICENCE),
                                      "classes": classes }

# ==========================================================================
# Code to generate the Python 3 methods module.
# ==========================================================================

# NOTE: This is almost exactly the same as the Python 2 code, so we just
# re-use all of its code, except the module template, which differs in the
# form of the import statement.

PYTHON3_MODULE_TEMPLATE = '''# === AUTO-GENERATED - DO NOT EDIT ===

# --------------------------------------------------------------------------
%(licence)s
# --------------------------------------------------------------------------

"""
Web service API methods. This module is fully auto-generated, and contains
the Python equivalent of the `XxxMethods` Java classes for executing all API
methods.
"""

from .connection import IbisException

%(classes)s'''

def generate_python3_module(app):
    """
    Generate the Python 3 code for the entire methods module, containing all
    the XxxMethods classes, using the PYTHON3_MODULE_TEMPLATE.
    """
    classes = "\n".join(generate_python_class(x) for x in app.classes)

    return PYTHON3_MODULE_TEMPLATE % { "licence": comment_out(LICENCE),
                                       "classes": classes }

# ==========================================================================
# Code to generate the PHP client methods classes.
# ==========================================================================

PHP_METHOD_TEMPLATE = """
    %(method_docs)s
    public function %(method_name)s(%(method_params)s)
    {
        $pathParams = array(%(path_params)s);
        $queryParams = array(%(query_params)s);
        $formParams = array(%(form_params)s);
        $result = $this->conn->invokeMethod("%(method_kind)s",
                                            '%(method_path)s',
                                            $pathParams,
                                            $queryParams,
                                            $formParams);
        if (isset($result->error))
            throw new IbisException($result->error);
        return %(result)s;
    }
"""

def php_type(java_type):
    """
    Returns the PHP corresponding to the specified Java type. This is
    used only in the PHP docs.
    """
    if java_type == "java.lang.Long": return "int"
    if java_type == "java.lang.String": return "string"
    if java_type == "java.util.List": return "string"
    if java_type.startswith("java.util.List<"): return java_type[15:-1]+"[]"
    if java_type.startswith("uk.ac.cam.ucs.ibis.dto."): return java_type[23:]
    return java_type

def javadocs_to_phpdocs(docs, line_prefix=""):
    """
    Convert some javadocs to PHP docs. This is more-or-less plain text, but
    can contain some javadoc tags and reST (reStructuredText) markup.
    """
    docs = docs.strip()

    # Remove start and end javadoc comments
    if docs.startswith("/**"): docs = docs[3:].strip()
    if docs.endswith("*/"): docs = docs[:-2].strip()

    # Remove any comment line prefixes
    docs = re.sub("(?m)^\\s*[*][ ]?", "", docs)

    # == HTML tag processing ==

    # Replace HTML headings with reST headings
    docs = re.sub("(?s)<h[1-5][^>]*>(.+?)</h[1-5]>", "**\\1**", docs)

    # Replace <b>XXX</b> with **XXX**
    docs = re.sub("(?s)<b>(.+?)</b>", "**\\1**", docs)

    # Replace single-line <code>XXX</code> blocks with ``XXX``
    docs = re.sub("<code[^>]*>(.+?)</code>", "``\\1``", docs)

    # Replace <li> with plain text "*" bullet points (reST format)
    docs = list_items_to_text(docs)

    # Remove any other HTML tags, except for <code> and <pre> blocks
    docs = re.sub("<(?!code|/code|pre|/pre)[^>]+>", "", docs)

    # == End of HTML tag processing ==

    # == Javadoc tag processing ==

    # Remove parameters from method links, since ApiGen doesn't support them.
    # I.e., replace {@link xxx(yyy)} with {@link xxx()}. While we're at it,
    # remove any link text from such links, since ApiGen doesn't support that
    # either. I.e., replace {@link xxx(yyy) zzz} with {@link xxx()} too.
    docs = re.sub("[{]@link\\s+([^(}]+)[(][^)}]*[)][^}]*[}]",
                  "{@link \\1()}", docs)

    # Remove any link text from links. I.e., replace {@link xxx yyy} with
    # {@link xxx}.
    docs = re.sub("[{]@link\\s+([^}\\s]+)\\s+[^}]*[}]", "{@link \\1}", docs)

    # Remove any qualified methods from links, since ApiGen doesn't support
    # them. This risks breaking the link entirely, but there isn't any other
    # good solution. I.e., replace {@link xxx#yyy} with {@link yyy}.
    docs = re.sub("[{]@link\\s+[^}\\s#]+#([^}]+)[}]", "{@link \\1}", docs)

    # Finally, strip off any # prefixes from unqualified method links. I.e.,
    # replace {@link #xxx} with {@link xxx}
    docs = re.sub("[{]@link\\s+#([^}]+)[}]", "{@link \\1}", docs)

    # Replace {@code XXX} tags with ``XXX``
    docs = re.sub("[{]@code\\s+([^}]+)[}]", "``\\1``", docs)

    # Replace {@literal XXX} with XXX (no special handling)
    docs = re.sub("[{]@literal\\s+([^}]+)[}]", "\\1", docs)

    # Add the specified line prefix to each line
    if line_prefix: docs = re.sub("(?m)^", line_prefix, docs)

    # Strip off end-of-line whitespace
    docs = re.sub("(?m)\\s+$", "\n", docs)

    return docs

def generate_php_method(method):
    """
    Generate the PHP code for a single web service API method, using the
    PHP_METHOD_TEMPLATE.
    """
    # PHP docs for the method - convert to plain text and back to Javadocs
    # to remove any HTML tags
    docs = javadocs_to_phpdocs(method.get_docs())
    docs = "/**\n     %s\n     */" % comment_out(docs, "     *")

    # Add the parameter types to the PHP docs
    for param in method.all_params:
        docs = re.sub("[*] @param "+param.name,
                      "* @param "+php_type(param.java_type)+" $"+param.name,
                      docs)

    # Add the return type to the PHP docs
    docs = re.sub("[*] @return ",
                  "* @return %s " % php_type(method.result_type), docs)

    # Method parameters
    last_required_param = 0
    for idx, param in enumerate(method.all_params):
        if ("@param %s [required]" % param.name) in method.docs:
            last_required_param = idx

    param_names = []
    for idx, param in enumerate(method.all_params):
        if idx > last_required_param:
            param_names.append("$"+param.name+"=null")
        else:
            param_names.append("$"+param.name)
    method_params = aligned_output([param_names], 21 + len(method.name))

    # Path parameters
    param_names = [ '"'+x.name+'"' for x in method.path_params ]
    param_vars = [ "=> $"+x.name for x in method.path_params ]
    path_params = aligned_output([param_names, param_vars], 28, 1)

    # Query parameters
    param_names = [ '"'+x.name+'"' for x in method.query_params ]
    param_vars = [ "=> $"+x.name for x in method.query_params ]
    query_params = aligned_output([param_names, param_vars], 29, 1)

    # Form parameters
    param_names = [ '"'+x.name+'"' for x in method.form_params ]
    param_vars = [ "=> $"+x.name for x in method.form_params ]
    form_params = aligned_output([param_names, param_vars], 28, 1)

    # Method path - replace any placeholders with PHP/Java-style format
    # specifiers
    path = method.path
    param_number = 1
    while re.search("[{][^}]+[}]", path):
        path = re.sub("[{][^}]+[}]", "%%%d$s" % param_number, path, 1)
        param_number += 1

    # Final method result
    if method.result_type == "boolean":
        result = 'strcasecmp($result->value, "true") == 0'
    elif method.result_type == "int":
        result = "intval($result->value)"
    else:
        result = "$result->%s" % method.result_field.replace(".", "->")

    return PHP_METHOD_TEMPLATE % { "method_docs": docs,
                                   "method_name": method.name,
                                   "method_params": method_params,
                                   "path_params": path_params,
                                   "query_params": query_params,
                                   "form_params": form_params,
                                   "method_kind": method.kind,
                                   "method_path": path,
                                   "result": result }

PHP_CLASS_TEMPLATE = """<?php
/* === AUTO-GENERATED - DO NOT EDIT === */

/*%(licence)s*/

require_once dirname(__FILE__) . "/../client/IbisException.php";

%(class_docs)s
class %(class_name)s
{
    // The connection to the server
    private $conn;

    /**
     * Create a new %(class_name)s object.
     *
     * @param ClientConnection $conn The ClientConnection object to use to
     * invoke methods on the server.
     */
    public function __construct($conn)
    {
        $this->conn = $conn;
    }
%(methods)s}
"""

def generate_php_class(cls):
    """
    Generate the Java code for a single XxxMethods class, using the
    JAVA_CLASS_TEMPLATE.
    """
    docs = javadocs_to_phpdocs(cls.docs)
    docs = "/**\n %s\n */" % comment_out(docs, " *")
    methods = "".join(generate_php_method(x) for x in cls.methods)

    return PHP_CLASS_TEMPLATE % { "licence": LICENCE,
                                  "class_docs": docs,
                                  "class_name": cls.name,
                                  "methods": methods }

# ==========================================================================
# Main entry point.
# ==========================================================================

if __name__ == "__main__":

    # Parse the command line arguments
    num_args = len(sys.argv)
    if num_args > 1 and (sys.argv[1][:2] == "-h" or sys.argv[1] == "--help"):
        sys.stdout.write(usage)
        sys.exit(0)

    arg = 1
    while arg < num_args:
        if sys.argv[arg] == "-lang":
            arg += 1
            if arg >= num_args:
                error("No language specified")
            lang = sys.argv[arg].lower()
            arg += 1
        elif sys.argv[arg] == "-d":
            arg += 1
            if arg >= num_args:
                error("No output directory specified")
            out_dir = sys.argv[arg]
            arg += 1
        elif arg == num_args-1:
            wadl_file = sys.argv[arg]
            arg += 1
        else:
            error("Invalid option: '%s'" % sys.argv[arg])

    if wadl_file == None:
        error("No WADL file specified")

    # Read and parse the WADL file
    doc = xml.dom.minidom.parse(wadl_file)
    app = Application(doc.documentElement)

    # Create/update the output file(s) as necessary
    if lang == "java":
        for cls in app.classes:
            filename = os.path.join(out_dir, cls.name+".java")
            content = generate_java_class(cls)
            update_file_if_changed(filename, content)
    elif lang == "python":
        filename = os.path.join(out_dir, "methods.py")
        content = generate_python_module(app)
        update_file_if_changed(filename, content)
    elif lang == "python3":
        filename = os.path.join(out_dir, "methods.py")
        content = generate_python3_module(app)
        update_file_if_changed(filename, content)
    elif lang == "php":
        for cls in app.classes:
            filename = os.path.join(out_dir, cls.name+".php")
            content = generate_php_class(cls)
            update_file_if_changed(filename, content)
    else:
        error("Unsupported language: '%s'" % lang)
